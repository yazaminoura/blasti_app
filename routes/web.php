<?php

use App\Models\Voyage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VilleController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\VoyageController;
use App\Http\Controllers\AutocarController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocieteController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\EquipementController;
use App\Http\Controllers\TypeVoyageController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\AutocarOptionController;
use App\Http\Controllers\ModeReglementController;
use App\Http\Controllers\AutocarEquipementController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ApparenceController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Client\ReservationController as ClientReservationController;
use App\Http\Controllers\Client\PaymentController;
use App\Http\Controllers\Client\Profile\DashboardController as ProfileDashboardController;
use App\Http\Controllers\Client\Profile\MonprofileController;
use App\Http\Controllers\Client\Profile\ParametreController;
use App\Http\Controllers\Client\Profile\ReservationController as ProfileReservationController;

// ======= Public =======
Route::get('/', function () {
    // Next departures only, soonest first
    $voyages = Voyage::bookable()
        ->with(['villeDepart', 'villeArrivee', 'typeVoyage', 'autocar.societe'])
        ->withCount('reservations')
        ->take(12)
        ->get();

    // Real numbers for the home page (no invented figures)
    $stats = [
        'villes' => \App\Models\Ville::has('voyagesDepart')->orHas('voyagesArrivee')->count(),
        'departs' => Voyage::bookable()->count(),
        'societes' => \App\Models\Societe::has('autocars')->count(),
        'billets' => \App\Models\Reservation::count(),
    ];

    // every city where a bus stops, intermediate stops included (Imouzzer, Ifrane...)
    $villesRecherche = \App\Models\Ville::whereHas('arrets')->orderBy('ville')->get();

    return view('welcome', compact('voyages', 'stats', 'villesRecherche'));
})->name('home');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');

// Information pages
Route::get('/destinations', [\App\Http\Controllers\PageController::class, 'destinations'])->name('pages.destinations');
Route::get('/compagnies', [\App\Http\Controllers\PageController::class, 'compagnies'])->name('pages.compagnies');
Route::get('/aide', [\App\Http\Controllers\PageController::class, 'aide'])->name('pages.aide');
Route::get('/a-propos', [\App\Http\Controllers\PageController::class, 'aPropos'])->name('pages.apropos');
// CGV, privacy policy, legal notice
Route::get('/legal/{page}', [\App\Http\Controllers\PageController::class, 'legal'])->whereIn('page', ['conditions', 'confidentialite', 'mentions-legales'])->name('pages.legal');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/voyages/list', [VoyageController::class, 'listVoyages'])->name('voyages.list');
// Search results (home search form): public, login is only asked when booking
Route::get('/client/voyages', [VoyageController::class, 'clientIndex'])->name('voyages.client.index');
Route::get('/client/societes/{societe}/showVoyageSociete', [SocieteController::class, 'showVoyageSociete'])->name('client.societes.showVoyageSociete.index');

// Voyage page with the seat map: guests may look, choosing a seat opens the sign in / sign up popup
Route::get('/client/reservations/{voyage}', [ClientReservationController::class, 'index'])->whereNumber('voyage')->name('client.reservations.show');
// full bus: "Prévenez-moi" e-mail when a seat frees up
Route::post('/client/reservations/{voyage}/alerte', [ClientReservationController::class, 'alerte'])->whereNumber('voyage')->middleware('throttle:10,1')->name('client.reservations.alerte');
Route::get('/client/create/reservation', [ClientReservationController::class, 'create'])->name('client.create.reservation');
Route::get('/detail/voyage', [VoyageController::class, 'detail'])->name('voyage.detail');

// QR code printed on each ticket: signed link, so only a real ticket opens it (for the driver / agent at boarding)
Route::get('/billet/{reservation}/verifier', [ClientReservationController::class, 'verify'])->middleware('signed')->name('ticket.verify');
// "I'm coming" button of the presence e-mail (unpaid tickets, see config safar.confirmation)
// ticket PDF from the link shared on WhatsApp (signed, valid until 2 days after the trip)
Route::get('/billet/{reservation}/pdf', [ClientReservationController::class, 'sharedPdf'])->middleware(['signed', 'throttle:30,1'])->name('ticket.pdf.partage');
Route::get('/billet/{reservation}/presence', [ClientReservationController::class, 'presence'])->middleware(['signed', 'throttle:20,1'])->name('ticket.presence');

// CMI card payment: server callback + the pages the bank sends the client back to (signed by CMI, no session)
Route::post('/paiement/cmi/callback', [PaymentController::class, 'callback'])->name('payment.cmi.callback');
Route::match(['get', 'post'], '/paiement/{reservation}/ok', [PaymentController::class, 'ok'])->name('payment.cmi.ok');
Route::match(['get', 'post'], '/paiement/{reservation}/echec', [PaymentController::class, 'fail'])->name('payment.cmi.fail');

// AJAX filters
Route::get('/filter-voyages', [VoyageController::class, 'filter'])->name('voyages.filter');
Route::get('/filter-multiple', [VoyageController::class, 'filter_sidebar'])->name('voyages.filtermultiple');
Route::get('/filter-search', [VoyageController::class, 'search'])->name('voyages.search');

// The toggle answers 401 JSON itself for guests (used by the heart buttons)
Route::post('/wishlist/toggle/{voyage}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr', 'ar'])) {
        session(['locale' => $locale]);
    }
    // Only go back to a page of this site (the Referer header could point anywhere)
    $previous = url()->previous();

    return str_starts_with($previous, url('/')) ? redirect()->to($previous) : redirect()->route('home');
})->name('lang.switch');

// Breeze page kept as an alias (email verification redirects here): the client area is the real dashboard
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->isadmin ? 'admin' : 'client.profile.dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // ======= Client =======
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');

    // Réservations: the seat map is public (see above), booking needs a verified email (the tickets are sent there)
    // seat map → payment page (summary + payment choice) → booking → order page with every ticket
    Route::post('/reservation/panier', [ClientReservationController::class, 'checkout'])->middleware('verified')->name('client.reservations.checkout');
    Route::get('/reservation/paiement', [ClientReservationController::class, 'payment'])->middleware('verified')->name('client.reservations.payment');
    Route::post('/reservation/promo', [ClientReservationController::class, 'promo'])->middleware(['verified', 'throttle:20,1'])->name('client.reservations.promo');
    Route::post('/client/reservations', [ClientReservationController::class, 'store'])->middleware(['verified', 'throttle:20,1'])->name('client.reservations.store');
    Route::get('/commande/{commande}', [ClientReservationController::class, 'order'])->name('client.commande.show');
    Route::get('/commande/{commande}/billets.pdf', [ClientReservationController::class, 'downloadOrder'])->name('client.commande.download');
    // card payment test page (local PC without CMI keys only, see Cmi::testMode)
    Route::post('/paiement/{reservation}/test', [PaymentController::class, 'test'])->name('payment.test');

    // Tickets
    Route::get('/ticket/{id}', [ClientReservationController::class, 'show'])->name('ticket.show');
    Route::get('/ticket/{id}/download', [ClientReservationController::class, 'download'])->name('ticket.download');
    Route::post('/client/reservations/{reservation}/annuler', [ClientReservationController::class, 'cancel'])->name('client.reservations.cancel');
    // move a ticket to another departure / seat of the same trip (until safar.modification_heures before boarding)
    // review of a finished trip (one per ticket)
    Route::get('/client/reservations/{reservation}/avis', [\App\Http\Controllers\Client\AvisController::class, 'create'])->name('client.avis.create');
    Route::post('/client/reservations/{reservation}/avis', [\App\Http\Controllers\Client\AvisController::class, 'store'])->middleware('throttle:10,1')->name('client.avis.store');
    Route::get('/client/reservations/{reservation}/changer', [\App\Http\Controllers\Client\TicketChangeController::class, 'edit'])->name('client.reservations.change');
    Route::put('/client/reservations/{reservation}/changer', [\App\Http\Controllers\Client\TicketChangeController::class, 'update'])->middleware('throttle:10,1')->name('client.reservations.change.update');
    Route::get('/paiement/{reservation}/cmi', [PaymentController::class, 'start'])->middleware('verified')->name('payment.cmi.start');

    // Profil client
    Route::get('/client/profile/reservations', [ProfileReservationController::class, 'index'])->name('client.profile.reservations.index');
    Route::get('/client/profile/dashboard', [ProfileDashboardController::class, 'index'])->name('client.profile.dashboard.index');
    Route::get('/client/profile/monprofile', [MonprofileController::class, 'index'])->name('client.profile.monprofile.index');
    Route::get('/client/profile/parametres', [ParametreController::class, 'index'])->name('client.profile.parametres.index');
    Route::put('/client/profile/parametres', [ParametreController::class, 'update'])->name('client.profile.parametres.update');
    Route::delete('/profile/delete-image', [ParametreController::class, 'deleteProfileImage'])->name('profile.delete-image');

    // ======= Admin =======
    // "admin" = back-office account, "admin.permission" = role permission of each page
    Route::middleware(['admin', 'admin.permission'])->group(function () {
        Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin');

        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/clients', [UserController::class, 'clients'])->name('admin.clients.index');
        // one client: details, bookings, identity + password (never admin access: that is on the team page)
        Route::get('/admin/clients/{user}', [UserController::class, 'showClient'])->whereNumber('user')->name('admin.clients.show');
        Route::put('/admin/clients/{user}', [UserController::class, 'updateClient'])->whereNumber('user')->name('admin.clients.update');
        Route::put('/admin/clients/{user}/password', [UserController::class, 'updatePassword'])->whereNumber('user')->name('admin.clients.update-password');
        Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::put('/admin/users/{user}/password', [UserController::class, 'updatePassword'])->name('admin.users.update-password');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        // super admin: log a team account out of every browser
        Route::post('/admin/users/{user}/deconnecter', [UserController::class, 'deconnecter'])->name('admin.users.deconnecter');

        Route::get('/admin/roles/create', [RoleController::class, 'create'])->name('admin.roles.create');
        Route::post('/admin/roles', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/admin/roles/{role}/edit', [RoleController::class, 'edit'])->name('admin.roles.edit');
        Route::put('/admin/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/admin/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');

        // CRUD (no "show" pages exist for these resources)
        Route::resource('autocars', AutocarController::class)->except('show');
        Route::resource('societes', SocieteController::class)->except('show');
        Route::resource('voyages', VoyageController::class)->except('show');
        Route::resource('villes', VilleController::class)->except('show');
        Route::resource('type_voyages', TypeVoyageController::class)->except('show');
        Route::resource('modeReglements', ModeReglementController::class)->except('show');
        Route::resource('autocaroptions', AutocarOptionController::class)->except('show');
        Route::resource('autocarequipements', AutocarEquipementController::class)->except('show');
        Route::resource('options', OptionController::class)->except('show');
        Route::resource('equipements', EquipementController::class)->except('show');
        Route::resource('promotions', \App\Http\Controllers\Admin\PromotionController::class)->except('show');
        // red flags of the bus door: scanned "to pay", never paid nor boarded
        Route::get('/admin/alertes', [\App\Http\Controllers\Admin\AlertesController::class, 'index'])->name('admin.alertes');
        // sales dashboard (by company, route, month)
        Route::get('/admin/statistiques', [\App\Http\Controllers\Admin\StatistiquesController::class, 'index'])->name('admin.statistiques');
        // reviews moderation
        Route::get('/admin/avis', [\App\Http\Controllers\Admin\AvisController::class, 'index'])->name('avis.index');
        Route::patch('/admin/avis/{avis}/publier', [\App\Http\Controllers\Admin\AvisController::class, 'publier'])->name('avis.publier');
        Route::delete('/admin/avis/{avis}', [\App\Http\Controllers\Admin\AvisController::class, 'destroy'])->name('avis.destroy');

        // Réservations admin
        Route::get('/reservation/admin/list', [ReservationController::class, 'indexAdmin'])->name('reservation.admin.index');
        // counter sales: pick the departure and the seats, the traveller, take the money, hand the tickets
        Route::get('/admin/guichet', [\App\Http\Controllers\Admin\GuichetController::class, 'index'])->name('reservation.admin.guichet');
        Route::post('/admin/guichet', [\App\Http\Controllers\Admin\GuichetController::class, 'store'])->name('reservation.admin.guichet.vendre');
        Route::get('/admin/guichet/vente/{commande}', [\App\Http\Controllers\Admin\GuichetController::class, 'vente'])->name('reservation.admin.guichet.vente');
        Route::get('/admin/guichet/vente/{commande}/billets.pdf', [\App\Http\Controllers\Admin\GuichetController::class, 'pdf'])->name('reservation.admin.guichet.pdf');
        // receipt printer (80 / 58 mm roll) or any printer: one small ticket per seat
        Route::get('/admin/guichet/vente/{commande}/imprimer', [\App\Http\Controllers\Admin\GuichetController::class, 'imprimer'])->name('reservation.admin.guichet.imprimer');
        // fix the passenger name typed at the counter (typo)
        Route::patch('/admin/guichet/billet/{reservation}/passager', [\App\Http\Controllers\Admin\GuichetController::class, 'passager'])->name('reservation.admin.guichet.passager');
        Route::get('/reservation/admin/scanner', [ReservationController::class, 'scanner'])->name('reservation.admin.scanner');
        // one ticket checked from the scanner page (JSON): logged in the scans table
        Route::post('/reservation/admin/scanner', [ReservationController::class, 'scan'])->middleware('throttle:120,1')->name('reservation.admin.scan');
        Route::get('/reservation/admin/{reservation}/show', [ReservationController::class, 'show'])->name('reservation.admin.show');
        Route::delete('/reservation/admin/{reservation}', [ReservationController::class, 'destroy'])->name('reservation.admin.destroy');
        Route::patch('/reservation/admin/{reservation}/payer', [ReservationController::class, 'payer'])->name('reservation.admin.payer');
        Route::patch('/reservation/admin/{reservation}/rembourser', [ReservationController::class, 'rembourser'])->name('reservation.admin.rembourser');
        // controller at the bus door, from the page opened by the ticket's QR code
        Route::patch('/reservation/admin/{reservation}/embarquer', [ReservationController::class, 'embarquer'])->name('reservation.admin.embarquer');
        // passenger list of a bus (printable), for the controller / driver
        Route::get('/voyages/{voyage}/passagers', [VoyageController::class, 'passagers'])->name('voyages.passagers');
        // recurring trips: copy a voyage on chosen weekdays over a period
        Route::get('/voyages/{voyage}/programmer', [VoyageController::class, 'programmer'])->name('voyages.programmer');
        Route::post('/voyages/{voyage}/programmer', [VoyageController::class, 'programmerStore'])->name('voyages.programmer.store');

        // Logo color and name (super admin only, see AdminPermission)
        Route::get('/admin/apparence', [ApparenceController::class, 'edit'])->name('admin.apparence.edit');
        Route::put('/admin/apparence', [ApparenceController::class, 'update'])->name('admin.apparence.update');
        // public contact details + social links (super admin only, see AdminPermission)
        Route::get('/admin/coordonnees', [\App\Http\Controllers\Admin\CoordonneesController::class, 'edit'])->name('admin.coordonnees.edit');
        Route::put('/admin/coordonnees', [\App\Http\Controllers\Admin\CoordonneesController::class, 'update'])->name('admin.coordonnees.update');
        Route::post('/admin/coordonnees/test-mail', [\App\Http\Controllers\Admin\CoordonneesController::class, 'testMail'])->middleware('throttle:5,1')->name('admin.coordonnees.test-mail');

        // Export Excel (CSV)
        Route::get('/admin/export/reservations', [ExportController::class, 'reservations'])->name('admin.export.reservations');
        Route::get('/admin/export/voyages', [ExportController::class, 'voyages'])->name('admin.export.voyages');
        Route::get('/admin/export/users', [ExportController::class, 'users'])->name('admin.export.users');
        Route::get('/admin/export/autocars', [ExportController::class, 'autocars'])->name('admin.export.autocars');
    });
});

require __DIR__.'/auth.php';
