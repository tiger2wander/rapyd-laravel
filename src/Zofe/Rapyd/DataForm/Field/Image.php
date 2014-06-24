<?php

namespace Zofe\Rapyd\DataForm\Field;

use Illuminate\Support\Facades\Form;
use Illuminate\Support\Facades\Input;
use Intervention\Image\ImageManagerStatic as ImageManager;

class Image extends File
{
    public $type = "image";
    protected $image;

    protected $image_callable;
    protected $resize = array();
    protected $fit = array();

    /**
     * store a closure to make something with ImageManager post process
     * @param callable $callable
     * @return $this
     */
    public function image(\Closure $callable)
    {
        $this->image_callable = $callable;
        return $this;
    }

    /**
     * shortcut to ImageManager resize
     * @param $width
     * @param $height
     * @param $filename
     * @return $this
     */
    public function resize($width, $height, $filename = null)
    {
        $this->resize[] = array('width'=>$width, 'height'=>$height,  'filename'=>$filename);
        return $this;
    }

    /**
     * shortcut to ImageManager fit
     * @param $width
     * @param $height
     * @param $filename
     * @return $this
     */
    public function fit($width, $height, $filename = null)
    {
        $this->fit[] = array('width'=>$width, 'height'=>$height,  'filename'=>$filename);
        return $this;
    }
    
    /**
     * after upload we can work with ImageManager to so some post process
     * @param bool $save
     * @return bool
     */
    public function autoUpdate($save = false)
    {
        parent::autoUpdate($save);
        if ($this->saved)
        {
            if (!$this->image)  $this->image = ImageManager::make($this->saved);
            
            if ($this->image_callable) {
                $callable = $this->image_callable;
                $callable($this->image);
            }

            if(count($this->resize)) {
                foreach($this->resize as $resize)
                {
                    $this->image->resize($resize["width"], $resize["height"]);
                    $this->image->save($resize["filename"]);
                }
            }
            
            if(count($this->fit)) {
                foreach($this->fit as $fit)
                {
                    $this->image->fit($fit["width"], $fit["height"]);
                    $this->image->save($fit["filename"]);
                }
            }
            return true;
        }
        
    }
    

    public function build()
    {
        $output = "";
        $this->attributes["class"] = "form-control";
        if (parent::build() === false)
            return;

        switch ($this->status) {
            case "disabled":
            case "show":

                if ($this->type == 'hidden' || $this->value == "") {
                    $output = "";
                } elseif ((!isset($this->value))) {
                    $output = $this->layout['null_label'];
                } else {
                    
                    $output =  '<img src="'.ImageManager::make($this->path.$this->value)->fit(50, 50)->encode('data-url').'">';
                    //$output = '<img src="'.route('rapyd.image.fit', array(50,50,$this->path.$this->value)).'" />';
                }
                $output = "<div class='help-block'>" . $output . "</div>";
                break;

            case "create":
            case "modify":
                $output = Form::file($this->db_name, $this->attributes);
                break;

            case "hidden":
                $output = Form::hidden($this->db_name, $this->value);
                break;

            default:;
        }
        $this->output = "\n" . $output . "\n" . $this->extra_output . "\n";
    }

}