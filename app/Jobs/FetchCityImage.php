<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Base\City;
use Storage;

class FetchCityImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $city;
    protected $reference;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(City $city, $reference = null)
    {
        $this->city = $city;
        $this->reference = $reference;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $url = "https://maps.googleapis.com/maps/api/place/photo?maxwidth=1200&photoreference=".$this->reference."&key=".env('GOOGLE_MAPS_API_KEY');

        $context = stream_context_create(array(
            'http' => array('ignore_errors' => true),
        ));

        $contents = file_get_contents($url, false, $context);
        $photoName = time() . '-' . $this->city->name;
        Storage::disk('s3')->put('city-thumbnails/'.$photoName, $contents);

        $this->city->image_url = Storage::disk('s3')->url('city-thumbnails/'.$photoName);
        $this->city->save();
    }
}
