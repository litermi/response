<?php

namespace Litermi\Logs\Services;

use Illuminate\Support\Str;

/**
 *
 */
class GetTrackerService
{

    /**
     * @return mixed
     */
    public static function execute()
    {
        $tracker = collect(debug_backtrace());
        $tracker = $tracker->filter( self::filterHasRoute() );
        $tracker = $tracker->map( self::mapRemoveExtraElement() );
        $tracker = $tracker->values();

        return $tracker;
    }

    private static function filterHasRoute(): callable
    {

        return function ( $track, $key ){


            if(array_key_exists('file', $track) === false){
                return false;
            }

            return Str::contains($track['file'],'app/');
        };
    }

    private static function mapRemoveExtraElement(): callable
    {

        return function ( $track, $key ){
            $newTrack = [];
            if(array_key_exists('file', $track)){
                $newTrack[ 'file' ] = $track[ 'file' ];
                $serverPath = $_SERVER["DOCUMENT_ROOT"];
                $serverPath = Str::replace('public', "", $serverPath);
                $newTrack['file'] = Str::replace($serverPath, "", $newTrack['file']);
            }
            if(array_key_exists('line', $track)) {
                $newTrack[ 'line' ] = $track[ 'line' ];
            }

            return $newTrack;
        };
    }

}
