<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Utils\MobInterface;
use App\Entity\User;
use App\Utils\Logger;

class IndexController extends AbstractController
{
    public function indexAction(Request $request, MobInterface $launcher, Logger $logger)
    {
        //$pay = new PaymentService();
        
        if($request->get('msg') !== null){
            $response = $launcher->get($request->get('msg'), true);
            
            //Something to write to txt log
            $log  = "====================================================================================".PHP_EOL.
                "request: ".$request->get('msg').PHP_EOL.
                "Response: ".$response.PHP_EOL.
                "-----------------------------------------------------------------------------------------".PHP_EOL;
            $logger->writeLog($log);
            
            
            return new Response($response);
        }
       // var_dump($request->request);
        return new Response("Oops! Bad request send");
    }
    
    public function deliverStatus(Request $request, Logger $logger){
        if($request->get("user_id") !== null 
            && $request->get("status") !== null){
            $user = $this->getDoctrine()->getManager()->getRepository(User::class)->find($request->get("user_id"));
            
          //Something to write to txt log
            $log  = "====================================================================================".PHP_EOL.
                "user: ".$user->getId()."- ".$user->getUsername()."".PHP_EOL.
                "sms_status: ".$request->get("status").PHP_EOL.
                "-----------------------------------------------------------------------------------------".PHP_EOL;
            $logger->writeLog($log);
            return new Response("OK");
        }
        return new Response("Bad request");
    }
}