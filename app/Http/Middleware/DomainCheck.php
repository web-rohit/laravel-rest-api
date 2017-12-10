<?php

namespace App\Http\Middleware;

use App\Models\Retailer;
use Closure;
use Log;
use Illuminate\Support\Facades\Auth;

class DomainCheck {

    public function __construct(Retailer $retailer) {
        $this->retailer = $retailer;
    }

    public function handle($request, Closure $next, $guard = 'api') {
        // Get the required roles from the route
        $headers = apache_request_headers();
        \Log::info(" Headers " . print_r($headers, true));
        \Log::info(" Headers " . print_r($request->getHttpHost(), true));
        try {
            $domain = $request->getHttpHost();
            if(isset($domain)) {
                $domain_status = $this->retailer
                    ->select('status')->where('website',$domain)
                    ->get()
                    ->toArray();
                   
                if(isset($domain_status[0]['status']) && strtolower($domain_status[0]['status']) == 'active') {
                    return $next($request);
                  //  return response(json_encode(['next_request' => 'api/v1/products']))
                  //      ->setStatusCode(200, 'success');
                }
            }

            return response(json_encode(['code'=>'100', 'error' => 'You are not authorised to use service, please contact to administrator']))
                    ->setStatusCode(404, 'You are not authorised to use service, please contact to administrator');  
			
            //print_r(apache_request_headers(), true);
            //exit;

            //TODO: call the retailer model and check the passed domain against the 'website' column.
            // if the value is matched then allow to pass other wise throw error message,
            // if(true) {
            //    return $next($request);
            //} else {
            //   return response(json_encode(['error' => 'You are not authorised to use service, please contact to administrator']))
            //            ->setStatusCode(404, 'You are not authorised to use service, please contact to administrator');   
            //} 
            
			
			
        } catch (\Exception $e) {
            return response(json_encode(['error' => $e->getMessage()]))
                            ->setStatusCode(405, $e->getMessage());
        }

    }

}
