<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Upload;
use DB;
use Log;

class ProductRepository extends BaseRepository {

    public $_uploadProductId;
    public $upload;

    public function __construct(Product $product, Upload $uplaod) {
        $this->model = $product;
        $this->upload = $uplaod;
    }

    public function importCsvFile() {
        $uploads = DB::table('upload_products')
                ->select('upload_products.id', 'upload_products.file_name', 'uploads.name', 'uploads.path')
                ->leftJoin('uploads', 'upload_products.file_name', '=', 'uploads.id')
                ->whereNull('upload_products.deleted_at')
                ->where('upload_products.status', 'pending')
                ->first();

        if (!empty($uploads)) {
            $this->_uploadProductId = $uploads->id;
            $file = $uploads->path;

            $this->updateFileStatus(array(
                'status' => 'importing'
            ));

            $products = $this->csvToArray($file);
            Log::write('info', 'Inserting loop is started... ');
            foreach ($products as &$value) {
                if ($value['retailer_code']) {
                    $value['retailer_id'] = \App\Models\Retailer::where('retailer_uid', '=', $value['retailer_code'])->first()->id;
                    unset($value['retailer_code']);
                }

                if (!empty($value['image link'])) {
                    $pathinfo = pathinfo($value['image link']);
                    if (copy($value['image link'], storage_path('uploads/') . $pathinfo['basename'])) {
                        $uploadObj = new $this->upload;
                        $uploadObj->name = $pathinfo['basename'];
                        $uploadObj->path = storage_path('uploads/') . $pathinfo['basename'];
                        $uploadObj->extension = $pathinfo['extension'];
                        $uploadObj->hash = uniqid();
                        $uploadObj->save();
                        sleep(5);
                        $value['product_image'] = $uploadObj->id;
                        unset($value['image link']);
                    }
                }
                Log::write('info', 'Inserting product ' . $value['sku']);
                $this->insertingProduct($value);
            }

            $this->updateFileStatus(array(
                'status' => 'success',
                'file_import_status' => 'Product (' . count($products) . ') are imported...',
            ));
        }
    }

    function csvToArray($filename = '', $delimiter = ',') {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header) {
                    $header = $row;
                } else {
                    Log::write('info', 'verifying array ' );
                    if ($this->verifyImportArray(array_combine($header, $row))) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }

    public function verifyImportArray($data) {
        $n = 0;
        foreach ($data as $key => $value) {
            if (empty($value) && $n < 3) {
                $this->updateFileStatus(array(
                    'status' => 'error',
                    'file_import_status' => $key . ' is empty, please check & reupload again',
                ));
                throw new \Exception('Retailer is not exist');
            }
            if ($key == 'retailer_code') {
                if (\App\Models\Retailer::where('retailer_uid', '=', $value)->exists() == false) {
                    $this->updateFileStatus(array(
                        'status' => 'error',
                        'file_import_status' => 'Retailer (' . $value . ') is not exist, please check',
                    ));
                    throw new \Exception('Retailer is not exist');
                }
            }

            if ($key == 'sku') {
                if (\App\Models\Product::where('sku', '=', $value)->exists()) {
                    $this->updateFileStatus(array(
                        'status' => 'error',
                        'file_import_status' => 'Product SKU (' . $value . ') is already exist, please check',
                    ));
                    throw new \Exception('SKU is already exist');
                }
            }
            $n++;
        }
        return true;
    }

    /**
     * update the status of upload products table
     * @param type $data
     */
    public function updateFileStatus($data) {
        if (!empty($data)) {
            $upload = \App\Models\Upload_Product::find($this->_uploadProductId);
            if ($upload) {
                foreach ($data as $key => $value) {
                    $upload->$key = $value;
                }
                $upload->save();
            }
        }
    }

    public function insertingProduct($data) {
        try {
            $proObj = new $this->model;
            DB::beginTransaction();
                $proObj->retailer_id = $data['retailer_id'];
                $proObj->sku = $data['sku'];
                $proObj->product_name = $data['name'];
                $proObj->product_image = $data['product_image'];
                $proObj->description = $data['description'];
                $proObj->bust_cm = $data['bust'];
                $proObj->waist_cm = $data['waist'];
                $proObj->hips_cm = $data['hips'];
                $proObj->arm_length = $data['arm legnth'];
                $proObj->leg_length = $data['leg legnth'];
                $proObj->height = $data['height'];
                $proObj->weight = $data['weight'];
                $proObj->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::write('error', 'Import ERROR >> ' . $e->getMessage());
            $this->updateFileStatus(array(
                'status' => 'error',
                'file_import_status' => $e->getMessage(),
            ));
            throw new \Exception($e->getMessage());
        }
        unset($proObj);
        return true;
    }
	
	public function getSizes($product_id)
	{
		$size = "";
        $data = [];
        $query = $this->model
                    ->where('id', $product_id);
		if($query->count() > 0) {
            $data = $query->get()->toArray();
			if(!empty($data)) {
				if(!empty($data[0]['product_size']))
				{
					$size = $data[0]['product_size'];
					return $size;
				}
				
            }
			
        }
		return $size;
	}

}
