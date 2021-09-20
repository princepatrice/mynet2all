<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;


class AuthController extends AbstractController
{
    
    //atributes

    private $apiLoginUrl="login.php";

    /**
     * @Route("/", name="login")
     */
    public function index(): Response
    {
        
       
       
        return $this->render('auth/login.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }



    /**
     * @Route("/login/request", name="login-request" )
     */
    public function login_request(Request $request,HttpClientInterface $client): Response
    {
        $data = $request->request->all();
       
        $response = $client->request('POST', $this->getParameter('API_URL').$this->apiLoginUrl, [
            'query' => [
                'email' => $data['username'],
                'passe' => $data['password']
            ]
        ]);


    $content = $response->getContent();
    $content_array = json_decode($content, true);

    //dd($content_array);
    $result = $content_array['server_response'][0];
    if ($result['status'] == 1) {
        $user =   $result;
        //dd($user);
        $session = $request->getSession();
        $session->set("currentuser",$user);

        return $this->redirectToRoute('dashboard');
    }
    else
    {
        return $this->redirectToRoute('login');
    }
}
/**
     * @Route("/logout", name="logout" )
     */
    public function logout(Request $request,HttpClientInterface $client): Response
    {
        
        $checkstatus=$this->check_authentificated($request);
       
        if(!$checkstatus["status"]){
           return $this->redirectToRoute('login');
        }
        $session = $request->getSession();
        $session->clear();

        return $this->redirectToRoute('login');
    
    }

 /**
     * @Route("/dashboard", name="dashboard" )
     */
    public function dashboard(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");
        //dd($user);
        return $this->render('auth/dashboard.html.twig', [
            'controller_name' => 'AuthController',
            'user'=>$user,

        ]);
    }


    /***************************local function *************************************/
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
