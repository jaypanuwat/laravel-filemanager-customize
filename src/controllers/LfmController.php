<?php namespace Unisharp\Laravelfilemanager\controllers;

use Unisharp\Laravelfilemanager\controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Input;
use Session;
/**
 * Class LfmController
 * @package Unisharp\Laravelfilemanager\controllers
 */
class LfmController extends Controller {

    /**
     * @var
     */
    public $file_location = null;
    public $dir_location = null;
    public $file_type = null;
    public $startup_view = null;
    protected $user;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->file_type = Input::get('type', 'Images'); // default set to Images.
        if ('Images' === $this->file_type) {
            $this->dir_location = Config::get('lfm.images_url');
            $this->file_location = Config::get('lfm.images_dir');
            $this->startup_view = Config::get('lfm.images_startup_view');
        } elseif ('Files' === $this->file_type) {
            $this->dir_location = Config::get('lfm.files_url');
            $this->file_location = Config::get('lfm.files_dir');
            $this->startup_view = Config::get('lfm.files_startup_view');
        } else {
            throw new \Exception('unexpected type parameter');
        }
        // $session_key = Config::get('lfm.session_key');
        // if(Session::has($session_key)){
        //     $this->user  = Session::get($session_key);
        // }

        $this->checkDefaultFolderExists('user');
        $this->checkDefaultFolderExists('share');
       
    }


    /**
     * Show the filemanager
     *
     * @return mixed
     */
    public function show()
    {
        // dd('tesrr');
        $working_dir = '/';
        $working_dir .= (Config::get('lfm.allow_multi_user')) ? $this->getUserSlug() : Config::get('lfm.shared_folder_name');
        
        $extension_not_found = ! extension_loaded('gd') && ! extension_loaded('imagick');
        return view('laravel-filemanager::index')
            ->with('working_dir', $working_dir)
            ->with('file_type', $this->file_type)
            ->with('startup_view', $this->startup_view)
            ->with('extension_not_found', $extension_not_found);
    }


    /*****************************
     ***   Private Functions   ***
     *****************************/


    private function checkDefaultFolderExists($type = 'share')
    {
        if ($type === 'user' && \Config::get('lfm.allow_multi_user') !== true) {
            return;
        }
        $path = $this->getPath($type);

        if (!File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
    }


    private function formatLocation($location, $type = null, $get_thumb = false)
    {
        if ($type === 'share') {
            return $location . Config::get('lfm.shared_folder_name');

        } elseif ($type === 'user') {
            return $location . $this->getUserSlug();

        }

        $working_dir = Input::get('working_dir');

        // remove first slash
        if (substr($working_dir, 0, 1) === '/') {
            $working_dir = substr($working_dir, 1);
        }


        $location .= $working_dir;

        if ($type === 'directory' || $type === 'thumb') {
            $location .= '/';
        }

        //if user is inside thumbs folder there is no need
        // to add thumbs substring to the end of $location
        $in_thumb_folder = preg_match('/'.Config::get('lfm.thumb_folder_name').'$/i',$working_dir);

        if ($type === 'thumb' && !$in_thumb_folder) {
            $location .= Config::get('lfm.thumb_folder_name') . '/';
        }

        return $location;
    }


    /****************************
     ***   Shared Functions   ***
     ****************************/


    public function getUserSlug()
    {
        
        $session_key = Config::get('lfm.session_key');
        // dd(Session::has($session_key));
        if(Session::has($session_key) && \Config::get('lfm.allow_multi_user')){
            $session_field = \Config::get('lfm.session_field');
            return Session::get($session_key)[$session_field];
        }
        return '';
    }


    public function getPath($type = null, $get_thumb = false)
    {
        $path = base_path() . '/' . $this->file_location;
        $path = $this->formatLocation($path, $type);
        if (!File::exists($path) && \Config::get('lfm.allow_multi_user')) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        return $path;
    }


    public function getUrl($type = null)
    {
        $url = $this->dir_location;

        $url = $this->formatLocation($url, $type);

        $url = str_replace('\\','/',$url);

        return $url;
    }


    public function getDirectories($path)
    {

        $thumb_folder_name = Config::get('lfm.thumb_folder_name');
        $all_directories = File::directories($path);
        $arr_dir = [];
        foreach ($all_directories as $directory) {  

            $directory = str_replace("\\","/",$directory);
            if(Config::get('lfm.show_thumb_folder') || (Config::get('lfm.show_thumb_folder') == false && strpos($directory,$thumb_folder_name) == false)){
                $dir_name = $this->getFileName($directory);
                if ($dir_name['short'] !== $thumb_folder_name ) {
                    $arr_dir[] = $dir_name;
                }
            }
        }
        return $arr_dir;
    }


    public function getFileName($file)
    {
        $lfm_dir_start = strpos($file, $this->file_location);
        $working_dir_start = $lfm_dir_start + strlen($this->file_location);
        $lfm_file_path = substr($file, $working_dir_start);
        $arr_dir = explode('/', $lfm_file_path);

        $arr_filename['short'] = end($arr_dir);
        $arr_filename['long'] = '/' . $lfm_file_path;

        return $arr_filename;
    }
}
