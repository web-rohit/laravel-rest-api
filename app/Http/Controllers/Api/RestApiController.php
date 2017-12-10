<?php

namespace App\Http\Controllers\Api;

use Validator;
use Eloquent;
use App\Repositories\UserRepository;
use App\Repositories\ProductRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RestApiController extends Controller
{
    public function __construct(UserRepository $userRepository, ProductRepository $productRepository) {
        $this->user_repository = $userRepository;
        $this->product_repository = $productRepository;
    }

    public function verifyemail($email) {
        try {
				$tokenKey = $this->user_repository->checkEmailIsExist($email);
			   if(!empty($tokenKey))
				{
					return response(json_encode(['code'=>'200','next_request' => 'api/v1/products', 'token' => $tokenKey]))
						->setStatusCode(200, 'success');
					exit();
					
				}
                else
				{
					return response(json_encode(['code'=>'400','next_request' => 'api/v1/register']))
						->setStatusCode(200, 'success');
					/* return response(json_encode(['code'=>'400', 'message' => 'Email id not exist, please provide a correct information']))
						->setStatusCode(400, 'success'); */
				}
            
        } catch (\Exception $exc) {
            return response(json_encode(['message' => $exc->getMessage()]))
                    ->setStatusCode(400, 'success');
        }
    }

	/**
	* 
	*/
	public function register(Request $request) {
		\Log::info(' post data >> ' . print_r($request->all(), true));
		$tokenKey = $this->user_repository->insertingUser($request->all());
		//return $tokenKey;
		if(!empty($tokenKey))
		{
			return response(json_encode(['code'=>'200', 'token' => $tokenKey]))
				->setStatusCode(200, 'success');
			exit();
			
		}
	}
	
	public function getproductsize($product_id)
	{
		$product_size = $this->product_repository->getSizes($product_id);
		return response(json_encode(['code'=>'200','product_size' => $product_size]))
						->setStatusCode(200, 'success');
		
	}
	
}
