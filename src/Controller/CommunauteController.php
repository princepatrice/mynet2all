<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/communaute")
 */
class CommunauteController extends AbstractController
{
    /**
     * @Route("/", name="communaute")
     */
    public function index(Request $request): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");
        return $this->render('communaute/index.html.twig', [
            'controller_name' => 'CommunauteController',
            'user'=>$user,
        ]);
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
