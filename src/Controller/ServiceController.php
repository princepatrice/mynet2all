<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/service")
 */


class ServiceController extends AbstractController
{
    private $tab=["1"=>"https://panel.magmaerp.online/public"];
    /**
     * @Route("/", name="service")
     */
    public function index(Request $request): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");
        //dd($user);
        return $this->render('service/index.html.twig', [
            'controller_name' => 'ServiceController',
            'user'=>$user,
        ]);
    }


        /**
     * @Route("/view/{id}/account/{idaccount}", name="service-view")
     */
    public function view(Request $request,$id,$idaccount): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
         $url = $this->tab[$id];
         return $this->redirect("$url/saas/$idaccount/authentication");
    }

    public function check_authentificated(Request $request){
        //return $this->api_key;
        $session = $request->getSession();
        $info=["status"=>true];
        if(!$session->get("currentuser")){
         $info = ["status"=>false];
        }
        return $info;

     }
}
