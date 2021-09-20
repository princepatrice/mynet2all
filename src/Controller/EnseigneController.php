<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
/**
 * @Route("/enseigne")
 */

class EnseigneController extends AbstractController
{
    private $apiMyEnseigneUrl = 'my_enseignes.php';

    /**
     * @Route("/", name="enseigne")
     */
    public function index(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");

        $responseMyEnseigne = $client->request('POST', $this->getParameter('API_URL').$this->apiMyEnseigneUrl, [
            'query' => [
                'id_entreprise' => $user["id_entreprise"]
            ]
        ]);

    $content = $responseMyEnseigne->getContent();//dd($content,$user->getId());
    $content_array = json_decode($content, true);
    //$MyEnseignes = $content_array['server_responses'][0]['founded'] === 0 ? [] : $content_array['server_responses'];
    $enseignes = []; 
    if(key_exists(0, $content_array['server_responses']))
    {
        if($content_array['server_responses'][0]['founded'] == 1)
        {
            $enseignes = $content_array['server_responses'];
        }
    }
 

        //dd($enseignes);
        return $this->render('enseigne/index.html.twig', [
            'controller_name' => 'EnseigneController',
            'user'=>$user,
            'enseignes'=>$enseignes
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
