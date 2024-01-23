<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'address',
        'lat',
        'lng',
        'img'
    ];

    public function checkOnTime() {
        $submissionTime = Carbon::parse($this->created_at);
        $deadlineTime = Carbon::parse('08:00:00');

        $deadlineDay = $this->created_at->format('Y-m-d');
        $deadline = Carbon::parse($deadlineDay)->setTime($deadlineTime->hour, $deadlineTime->minute, $deadlineTime->second);

        if ($submissionTime->lte($deadline)) {
            return 'On Time';
        } else {
            $lateDuration = $submissionTime->diff($deadline);
            $hours = $lateDuration->format('%h');
            $minutes = $lateDuration->format('%i');

            if ($hours >= 1) {
                return $hours . ' Hours and ' . $minutes . ' Minutes Late';
            } else {
                return $minutes . ' Minutes Late';
            }
        }
    }

    public function checkEarlyDeparture() {
        $departureTime = Carbon::parse($this->created_at);
        $agreedDepartureTime = Carbon::parse('17:00:00');

        if ($departureTime->lt($agreedDepartureTime)) {
            $earlyDuration = $agreedDepartureTime->diff($departureTime);
            $hours = $earlyDuration->format('%h');
            $minutes = $earlyDuration->format('%i');

            if ($hours >= 1) {
                return $hours . ' Hours and ' . $minutes . ' Minutes Early';
            } else {
                return $minutes . ' Minutes Early';
            }
        } else {
            return 'On Time or Late';
        }
    }


    
    protected $appends = [
        'loc',
    ];

    /**
     * ADD THE FOLLOWING METHODS TO YOUR Attendance MODEL
     *
     * The 'lat' and 'long' attributes should exist as fields in your table schema,
     * holding standard decimal latitude and longitude coordinates.
     *
     * The 'loc' attribute should NOT exist in your table schema, rather it is a computed attribute,
     * which you will use as the field name for your Filament Google Maps form fields and table columns.
     *
     * You may of course strip all comments, if you don't feel verbose.
     */

    /**
    * Returns the 'lat' and 'long' attributes as the computed 'loc' attribute,
    * as a standard Google Maps style Point array with 'lat' and 'lng' attributes.
    *
    * Used by the Filament Google Maps package.
    *
    * Requires the 'loc' attribute be included in this model's $fillable array.
    *
    * @return array
    */

    public function getLocAttribute(): array
    {
        return [
            "lat" => (float)$this->lat,
            "lng" => (float)$this->lng,
        ];
    }

    /**
    * Takes a Google style Point array of 'lat' and 'lng' values and assigns them to the     
    * 'lat' and 'long' attributes on this model.
    *
    * Used by the Filament Google Maps package.
    *
    * Requires the 'loc' attribute be included in this model's $fillable array.
    *
    * @param ?array $location
    * @return void
    */
    public function setLocAttribute(?array $location): void
    {
        if (is_array($location))
        {
            $this->attributes['lat'] = $location['lat'];
            $this->attributes['lng'] = $location['lng'];
            unset($this->attributes['loc']);
        }
    }

    /**
     * Get the lat and lng attribute/field names used on this table
     *
     * Used by the Filament Google Maps package.
     *
     * @return string[]
     */
    public static function getLatLngAttributes(): array
    {
        return [
            'lat' => 'lat',
            'lng' => 'lng',
        ];
    }

   /**
    * Get the name of the computed location attribute
    *
    * Used by the Filament Google Maps package.
    *
    * @return string
    */
    public static function getComputedLocation(): string
    {
        return 'loc';
    }

    protected static function booted() {
        static::creating(function($model) {
            $model->user_id = Auth::user()->id;
        });
    }
    protected function getFreshTimestamp()
    {
        return Carbon::now('Asia/Jakarta');
    }

    public function User()
    {
        return $this->belongsTo(related:User::class);
    }
}
