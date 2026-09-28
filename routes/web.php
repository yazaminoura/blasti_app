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
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/voyages/list', [VoyageController::class, 'listVoyages'])->name('voyages.list');
// Search results (home search form): public, login is only asked when booking
Route::get('/client/voyages', [VoyageController::class, 'clientIndex'])->name('voyages.client.index');
Route::get('/client/societes/{societe}/showVoyageSociete', [SocieteController::class, 'showVoyageSociete'])->name('client.societes.showVoyageSociete.index');

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

    Route::get('/detail/voyage', [VoyageController::class, 'detail'])->name('voyage.detail');

    // Réservations
    Route::get('/client/create/reservation', [ClientReservationController::class, 'create'])->name('client.create.reservation');
    Route::get('/client/reservations/{voyage}', [ClientReservationController::class, 'index'])->name('client.reservations.show');
    Route::post('/client/reservations', [ClientReservationController::class, 'store'])->middleware('throttle:20,1')->name('client.reservations.store');

    // Tickets
    Route::get('/ticket/{id}', [ClientReservationController::class, 'show'])->name('ticket.show');
    Route::get('/ticket/{id}/download', [ClientReservationController::class, 'download'])->name('ticket.download');
    Route::post('/client/reservations/{reservation}/annuler', [ClientReservationController::class, 'cancel'])->name('client.reservations.cancel');
    Route::get('/paiement/{reservation}/cmi', [PaymentController::class, 'start'])->name('payment.cmi.start');

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
        Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::put('/admin/users/{user}/password', [UserController::class, 'updatePassword'])->name('admin.users.update-password');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');

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

        // Réservations admin
        Route::get('/reservation/admin/list', [ReservationController::class, 'indexAdmin'])->name('reservation.admin.index');
        Route::get('/reservation/admin/{reservation}/show', [ReservationController::class, 'show'])->name('reservation.admin.show');
        Route::delete('/reservation/admin/{reservation}', [ReservationController::class, 'destroy'])->name('reservation.admin.destroy');
        Route::patch('/reservation/admin/{reservation}/payer', [ReservationController::class, 'payer'])->name('reservation.admin.payer');
        Route::patch('/reservation/admin/{reservation}/rembourser', [ReservationController::class, 'rembourser'])->name('reservation.admin.rembourser');

        // Logo color and name (super admin only, see AdminPermission)
        Route::get('/admin/apparence', [ApparenceController::class, 'edit'])->name('admin.apparence.edit');
        Route::put('/admin/apparence', [ApparenceController::class, 'update'])->name('admin.apparence.update');

        // Export Excel (CSV)
        Route::get('/admin/export/reservations', [ExportController::class, 'reservations'])->name('admin.export.reservations');
        Route::get('/admin/export/voyages', [ExportController::class, 'voyages'])->name('admin.export.voyages');
        Route::get('/admin/export/users', [ExportController::class, 'users'])->name('admin.export.users');
        Route::get('/admin/export/autocars', [ExportController::class, 'autocars'])->name('admin.export.autocars');
    });
});

require __DIR__.'/auth.php';
