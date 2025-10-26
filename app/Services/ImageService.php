<?php

namespace App\Services;

use App\Services\ImageKitService;

class ImageService
{
    private $imageKit;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKit = $imageKitService->getImageKit();
    }

    public function deleteFile($folder, $url) 
    {
      try {
        $file_name = pathinfo($url, PATHINFO_BASENAME);
        $existing_file = $this->imageKit->listFiles(array(
          "path" => $folder,
          "name" => $file_name,
        ));

        $existing_file_id = $existing_file->result[0]->fileId;

        $this->imageKit->deleteFile($existing_file_id);
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage()
        ], 500);
      }
    }

    public function uploadFile($folder, $file, $fileName, $tags) 
    {
      try {
        $isUrl = gettype($file) != "object" ? true : false;
        $file_extension = $isUrl ? "jpg" : $file->extension();
        $file_name = $fileName . "-" . time(). "." . $file_extension;

        $fileToUpload = [
          'file' => $isUrl ? $file : fopen($file->getRealPath(), 'r'),
          "fileName" => $file_name,
          "folder" => $folder,
          "tags" => $tags,
          "useUniqueFileName" => false,
        ];

        $upload_file = $this->imageKit->uploadFile($fileToUpload);
        $file_url = $upload_file->result->url;

        return $file_url;
      } catch (\Exception $e) {
        return response()->json([
          'status' => 'error',
          'message' => $e->getMessage()
        ], 500);
      }
    }

    public function deleteFolder($folder) 
    {
      $this->imageKit->deleteFolder($folder);
    }

    public function renameAndMoveFiles($destinationFolder, $currentNames, $newName)
    {
      foreach ($currentNames as $index => $nameSearch) {
        $nameSearch_split = explode("/", $nameSearch);
        $image_folders[] = implode("/", array_slice($nameSearch_split, 4, 3));
        $image_names[] = $nameSearch_split[7];
        $listFiles[] = $this->imageKit->listFiles(array(
          "path" => $image_folders[$index],
          "searchQuery" => 'name=' . $image_names[$index],
        ));

        $fileNames[] = !empty($listFiles[$index]->result) ? $listFiles[$index]->result[0]->name : null;
      }

      $sanitized_fileNames = array_filter($fileNames);

      foreach ($sanitized_fileNames as $index => $fileName) {
        $currentPath = $image_folders[$index] . '/' . $fileName;
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $file_name = $newName . "-" . time(). "." . $extension;
        $file_names[] = $file_name;

        $this->imageKit->rename([
          'filePath' => $currentPath,
          'newFileName' => $file_name,
          'purgeCache' => true,
        ]);
        
        $updatedPath[] = $image_folders[$index] . '/' . $file_name;
        
        $this->imageKit->move([
          'sourceFilePath' => $updatedPath[$index],
          'destinationPath' => $destinationFolder
        ]);
      }

      foreach (array_unique($image_folders) as $image_folder) {
        $this->deleteFolder($image_folder);
      }

      return $file_names;
  }
}
