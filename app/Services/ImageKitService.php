<?php

namespace App\Services;

use ImageKit\ImageKit;

class ImageKitService
{
    private $imageKit;

    public function __construct()
    {
        $public_key = config('imagekit.public_key');
        $your_private_key = config('imagekit.server_key');
        $url_end_point = config('imagekit.url');
        $this->imageKit = new ImageKit($public_key, $your_private_key, $url_end_point);
    }

    public function getImageKit()
    {
        return $this->imageKit;
    }
}
