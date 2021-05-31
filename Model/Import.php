<?php

namespace Custom\ImageUploader\Model;
use \Magento\Catalog\Model\Product\Gallery\EntryFactory;
use \Magento\Catalog\Model\Product\Gallery\GalleryManagement;
use \Magento\Framework\Api\ImageContentFactory;
class Import extends \Magento\Framework\Model\AbstractModel 
{
  private $productRepository;
  private $mediaGalleryEntryFactory;
  private $mediaGalleryManagement;
  private $imageContentFactory;
  public function __construct(
   \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
   \Magento\Framework\Filesystem\Driver\File $fileDriver,
    EntryFactory $mediaGalleryEntryFactory,
    GalleryManagement $mediaGalleryManagement,
    ImageContentFactory $imageContentFactory
 )
  { 
    $this->fileDriver               = $fileDriver;      
    $this->productRepository        = $productRepository;
    $this->mediaGalleryEntryFactory = $mediaGalleryEntryFactory;
    $this->mediaGalleryManagement   = $mediaGalleryManagement;
    $this->imageContentFactory      = $imageContentFactory;
  }

  public function imgImport($checksub,$checknow,$childpath){
    try {
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $dir = $objectManager->get('\Magento\Framework\App\Filesystem\DirectoryList');
      $directoryPath = $dir->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR).'/importexport/import/images/';
      
      if($childpath){
        if (!file_exists($directoryPath.''.$childpath.'/')) {
          mkdir($directoryPath.$childpath.'/', 0777, true);
        }
        $directoryPath = $directoryPath.''.$childpath.'/'; 
      }
      
      
      $html            = '';
      $foundImg        = 0;
      $foundImghtml    = '';
      $notfoundImghtml ='';
      $notfoundImg     = 0;
      if($checksub)
      {
       $all_files =  $this->getMultiDirectory($directoryPath);
      }else{
       $all_files = $this->getSingleDirectory($directoryPath); 
      }
      
      for ($i=0; $i<count($all_files); $i++)
      { 
        $image_name = $all_files[$i];
        $supported_format = array('gif','jpg','jpeg','png');
        $ext = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        
        if (in_array($ext, $supported_format))
        {
          $html.= $this->importImages($image_name,$checksub,$checknow);
          $foundImg++;
          $foundImghtml.='<p style="color:green;">'.$image_name.'</p>';
        } else {
          $notfoundImg++;    
          $notfoundImghtml.='<p style="color:red;">'.$image_name.'</p>';
          continue;
        }
      }
      
      $imagestatus ='';
      if($foundImg)
      {
      $imagestatus.= '<p style="color:green;">found images '.$foundImg.'</p>';
      $imagestatus.= $foundImghtml;
      }
      if($notfoundImg)
      {
      $imagestatus.= '<p style="color:red;">not found images '.$notfoundImg.'</p>';
      $imagestatus.= $notfoundImghtml;
      }
      if($checknow)
      {
       return $imagestatus.$html;   
      }else{
       return $html;          
      }

    } catch (\Exception $e) {
      return $e->getMessage();
    }

  }
  public function getSingleDirectory($gfg_folderpath)
  {
        $fileArr = [];
        // CHECKING WHETHER PATH IS A DIRECTORY OR NOT
        if (is_dir($gfg_folderpath))
        {
            // GETING INTO DIRECTORY
            $files = opendir($gfg_folderpath);
            {
                // CHECKING FOR SMOOTH OPENING OF DIRECTORY
                if ($files)
                {
                    //READING NAMES OF EACH ELEMENT INSIDE THE DIRECTORY
                    while (($gfg_subfolder = readdir($files)) !== false)
                    {
                        // CHECKING FOR FILENAME ERRORS
                        if ($gfg_subfolder != '.' && $gfg_subfolder != '..')
                        {
                            $fileArr[] = $gfg_folderpath . '' . $gfg_subfolder;
        
                        }
                    }
                }
            }
        }
        return $fileArr;
  }
  public function getMultiDirectory($dir, &$results = array())
  {
    $files = scandir($dir);
    foreach ($files as $key => $value)
    {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path))
        {
            $results[] = $path;
        }
        else if ($value != "." && $value != "..")
        {
            $this->getMultiDirectory($path, $results);
            $results[] = $path;
        }
    }

    return $results;
  }
  protected function importImages($url,$checksub,$checknow)
  {
    try {
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $image    = substr($url, strrpos($url, '/') + 1);
      $exploded = explode('_', $image);
      $position = (int)$exploded[2];
      array_pop($exploded);
      $sku      = implode('_', $exploded);
      $_product = $this->productRepository->get($sku);
      $filePath = $url;
      $html     = '';
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      $dir = $objectManager->get('\Magento\Framework\App\Filesystem\DirectoryList');
      
      /* Store the path of destination file */
      $destinationFilePath = $dir->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA).'/catalog/product/imp/'.$image;;

      if (!file_exists($dir->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA).'/catalog/product/imp/')) {
        mkdir($dir->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA).'/catalog/product/imp/', 0777, true);
      }    
      if($checknow!=1){
        /* Move File from images to copyImages folder */
          if( !copy($filePath, $destinationFilePath) ) {
            $html.= "<p style='color:red'>File can't be moved!"."</p>";
          }  
          else {  
            $html.= "<p style='color:green'>File has been moved!"."</p>";
            $existingMediaGalleryEntries = $_product->getMediaGalleryEntries();
            
            $imageProcessor = $objectManager->create('Magento\Catalog\Model\Product\Gallery\Processor');
            $productGallery = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Gallery');
            $images = $_product->getMediaGalleryImages();
            
            foreach($images as $child){
                $removeimage    = substr($child->getFile(), strrpos($child->getFile(), '/') + 1);
                if($removeimage==$image)
                {
                 $productGallery->deleteGallery($child->getValueId());
                 $imageProcessor->removeImage($_product, $child->getFile());
                }
                
            }
            $this->productRepository->save($_product);
            $product = $this->productRepository->get($sku);
            return $html.= $this->importProductImages($product,$destinationFilePath,$position,$checknow);
          }    
      }else{
            $product = $this->productRepository->get($sku);
            return $html.= $this->importProductImages($product,$destinationFilePath,$position,$checknow);
      }
       
      
      
    } catch (\Exception $e) {
             return $e->getMessage();
    }
  }

  protected function importProductImages(\Magento\Catalog\Model\Product $product,$destinationFilePath,$position,$checknow)
  {
    try {
        $html = '';    
        $i = $foundImages = 0;
      
        $imageAbsolutePath = $destinationFilePath;
        if($checknow!=1){
            if(!file_exists($imageAbsolutePath)) {
              return $html.= "<p style='color:red'> {$imageAbsolutePath} No images found for the product SKU {$product->getSku()} </p>";
            }
        }
        $flags = ($foundImages == 0) ? ['image','thumbnail','small_image'] : [];
        if($position!=1){
        $flags = ($foundImages == 0) ? ['additional_images'] : [];  
        }    
        if($checknow!=1){
                
        //$product->addImageToMediaGallery($imageAbsolutePath, $flags, false, false);
        $this->productRepository->save($product);
        //if($position!=1){
        $this->processMediaGalleryEntry($imageAbsolutePath, $product->getSku(), $product->getName(), $additional = true, $position);
        //}
        $html.= "<p style='color:green'> successfully {$imageAbsolutePath} imported with success for the product SKU {$product->getSku()} </p>";
        }else{
        $html.=  "<p style='color:green'>validation checked {$imageAbsolutePath} imported found for the product SKU {$product->getSku()} </p>";        
        }


      return $html;
    } catch (\Exception $e) {
      return $e->getMessage();
    }
    
  }
  
   /**
     * @param string $filePath
     * @param string $sku
     */
  public function processMediaGalleryEntry($filePath, $sku, $productName, $additional = true, $position)
    {
        $entry = $this->mediaGalleryEntryFactory->create();
    
        $entry->setFile($filePath)
            ->setMediaType('image')
            ->setDisabled(false)
            ->setLabel($productName);
        if($position==1)
        {
        $additional = false;    
        }
        if($additional)
        {
            $entry->setPosition($position);
            $entry->setTypes(['thumbnail']);
        }
        else
        {
            $entry->setTypes(['thumbnail', 'image', 'small_image']);
            $entry->setPosition($position);
        }
    
        $imageContent = $this->imageContentFactory->create();
    
        $imageContent->setType(mime_content_type($filePath))
            ->setName($productName)
            ->setBase64EncodedData(base64_encode(file_get_contents($filePath)));
    
        $entry->setContent($imageContent);
    
        $this->mediaGalleryManagement->create($sku, $entry);
    
    }
}
