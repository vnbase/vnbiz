<?php

class VnbizImage
{
    private $image;
    private $width;
    private $height;

    function __construct($file_path)
    {
        $this->image = new Imagick($file_path);
        $this->width = $this->image->getImageWidth();
        $this->height = $this->image->getImageHeight();
    }

    public function get_width()
    {
        return $this->width;
    }

    public function get_height()
    {
        return $this->height;
    }

    function scale($file_name, $width, $height, $type = 'webp')
    {
        $temp_image = clone $this->image;
        $temp_image->cropThumbnailImage($width, $height);
        $temp_image->writeImage($file_name);
        $temp_image->setImageFormat($type);
        // $im->setOption('webp:method', '6'); 
        // $temp_image->setImageCompressionQuality(50);
        $temp_image->destroy();
    }
}
