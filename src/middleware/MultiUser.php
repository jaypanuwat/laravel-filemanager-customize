<?php 

namespace Unisharp\Laravelfilemanager\middleware;

use Closure;
use Session;
class MultiUser
{
    public function handle($request, Closure $next)
    {
    	if (\Config::get('lfm.allow_multi_user') === true) {
            $session_key = \Config::get('lfm.session_key');
            if(Session::has($session_key)){
                $session_field = \Config::get('lfm.session_field');
                $new_working_dir = '/' . Session::get($session_key)[$session_field];

                $previous_dir = $request->input('working_dir');

                if ($previous_dir == null) {
                    $request->merge(['working_dir' => $new_working_dir]);
                } elseif (! $this->validDir($previous_dir)) {
                    $request->replace(['working_dir' => $new_working_dir]);
                }
            }
            
	    }

        return $next($request);
    }

    private function validDir($previous_dir)
    {
    	if (starts_with($previous_dir, '/' . \Config::get('lfm.shared_folder_name'))) {
    		return true;
        }
        $session_key = \Config::get('lfm.session_key');
        if(Session::has($session_key)){
            $session_field = \Config::get('lfm.session_field');
            if (starts_with($previous_dir, '/' . (string)Session::get($session_key)[$session_field])) {
            	return true;
            }
        }

        return false;
    }
}
