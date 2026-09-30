<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/** Sign up sends a verification link; booking needs a verified email (the ticket is sent there). */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        
        'name',
        // 'nom',
        'email', 
        'image',
        'telephone',    
        'pays',
        'region',
        'ville',              
        'adresse',
        'code_postal',
        'password',
        'isadmin',
    ];
    
    public function getProfileImagePathAttribute()
    {
        if (! $this->image || $this->image === 'default.jpg') {
            return asset('assets/img/users/default.jpg');
        }

        // Bundled images (e.g. the column default "assets/img/users/default.jpg") live in /public
        if (str_starts_with($this->image, 'assets/')) {
            return asset($this->image);
        }

        // Uploaded images live on the "public" disk (storage/app/public)
        return asset('storage/' . $this->image);
    }





    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    //=========== Relations ===========
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * No-shows: unpaid tickets whose bus has left without them being scanned — only counted on buses where
     * the controller did scan tickets (otherwise nobody used the scan and it proves nothing).
     */
    public function absences(): int
    {
        return $this->reservations()
            ->whereNull('paye_le')->whereNull('embarque_le')
            ->where(fn ($q) => $q->whereDate('date_depart', '<', today())
                ->orWhere(fn ($q) => $q->whereDate('date_depart', today())->where('heure_depart', '<', now()->format('H:i:s'))))
            ->whereExists(fn ($q) => $q->from('reservations as scanned')
                ->whereColumn('scanned.voyage_id', 'reservations.voyage_id')
                ->whereNotNull('scanned.embarque_le'))
            ->count();
    }

    /** Unpaid seats held on upcoming trips (pay at boarding). */
    public function siegesNonPayesAVenir(): int
    {
        return $this->reservations()
            ->where('statut', Reservation::CONFIRMEE)->whereNull('paye_le')
            ->where(fn ($q) => $q->whereDate('date_depart', '>', today())
                ->orWhere(fn ($q) => $q->whereDate('date_depart', today())->where('heure_depart', '>', now()->format('H:i:s'))))
            ->count();
    }

    /** "Pay at boarding" is refused after too many no-shows (config safar.absences_max): card only. */
    public function mayPayAtBoarding(): bool
    {
        $max = (int) config('safar.absences_max');

        return $max <= 0 || $this->absences() < $max;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole($role)
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * An admin without any role is the super admin: full access to the back office.
     * An admin with role(s) is a staff member limited to the permissions of those roles.
     */
    public function isSuperAdmin(): bool
    {
        // a company account (societe_id) is never super admin, even without a role
        return (bool) $this->isadmin && $this->roles->isEmpty() && ! $this->societe_id;
    }

    public function societe()
    {
        return $this->belongsTo(Societe::class);
    }

    public function hasPermission($permission)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permission, $this->permissionNames(), true);
    }

    /** Permission names of all the user's roles, loaded once per request. */
    private ?array $permissionNamesCache = null;

    public function permissionNames(): array
    {
        return $this->permissionNamesCache ??= $this->roles()
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }
}
