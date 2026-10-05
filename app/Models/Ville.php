<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ville extends Model
{
    use HasFactory;

    protected $fillable = [
        'ville',
        'image',
    ];

    /** Photos bundled with the site, used until the admin uploads one for the city. */
    private const BUNDLED_IMAGES = [
        'Fès' => 'assets/img/citys/360_F_190951678_ecT0d63mDdPO5GnbNE7q02fofcmrIRem.jpg',
        'Casablanca' => 'assets/img/citys/istockphoto-2158130578-612x612.jpg',
        'Rabat' => 'assets/img/citys/360_F_415148494_49dcXHrLBKS8eu2mjPWbHjG5CpLKgMva.jpg',
        'Tanger' => 'assets/img/citys/360_F_230277502_lVnQnE39sAc3PDf6NqjU9Ei3eNQoreYS.jpg',
        'Marrakech' => 'assets/img/citys/istockphoto-638948336-612x612.jpg',
        'Laayoune' => 'assets/img/citys/Yassine-Benkirane-16-1920x898-1-696x326.png',
        'Oujda' => 'assets/img/citys/Ville-Oujda-Maroc.jpg',
    ];

    /**
     * Resolves an image path into a proper public URL.
     * Correctly handles relative paths, avoids prepending /storage/ twice,
     * supports bundled assets, external URLs, and points to the public storage disk.
     */
    public static function resolveImageUrl(?string $image): ?string
    {
        if (! $image) {
            return null;
        }

        $img = trim($image);

        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }

        if (str_starts_with($img, 'assets/') || str_starts_with($img, '/assets/')) {
            return asset(ltrim($img, '/'));
        }

        // Avoid prepending '/storage/' twice if already stored as 'storage/...' or '/storage/...'
        $path = preg_replace('#^/?storage/#', '', $img);
        $path = ltrim($path, '/');

        return asset('storage/' . $path);
    }

    /** URL of the city photo, or null when there is none (the view then shows a plain tile). */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->image) {
            return self::resolveImageUrl($this->image);
        }

        return isset(self::BUNDLED_IMAGES[$this->ville]) ? asset(self::BUNDLED_IMAGES[$this->ville]) : null;
    }

    // Définir la relation avec le modèle Voyage
    public function voyagesDepart()
    {
        return $this->hasMany(Voyage::class, 'ville_depart_id');
    }

    public function voyagesArrivee()
    {
        return $this->hasMany(Voyage::class, 'ville_arrivee_id');
    }

    /** Every stop in this city (departure, intermediate or arrival). */
    public function arrets()
    {
        return $this->hasMany(VoyageArret::class);
    }
}


