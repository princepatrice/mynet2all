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
    private $rooturl='https://my-net2all.online/public/';
    //private $rooturl='https://127.0.0.1:8000/';
    private $apiCreateEnseigne='add_enseigne.php';
    private $apiGetEnseigne='oneEnseigne.php';
    private $apiUpdateEnseigneInfo='update_enseigne_info.php';

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

    /**
     * @Route("/create", name="enseigne-create")
     */
    public function create(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");

        return $this->render('enseigne/create.html.twig', [
            'controller_name' => 'EnseigneController',
            'user'=>$user
        ]);
    }

    /**
     * @Route("/create-request", name="enseigne-create-request")
     */
    public function create_request(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");

        $data = $request->request->all();
      
        $imgv=$request->files->get('file')??null;
        $img= null;
        if($imgv){
            $img=$this->rooturl.$this->uniquesave($request->files->get('file'));
        }

        $param = [
            "nom_enseigne"=>$data["nom_enseigne"],
            "code_enseigne"=>"Tmp-net2all-".rand(1000000000,9999999999),
            "phone"=>$data["phone"],
            "photo"=>$img,
            "id_entreprise"=>$user["id_entreprise"]
        ];


        //dd($param);

      /*  $enseigne = $this->ClientRequestContent($request,$client,'POST', 
        $this->getParameter('API_URL').$this->apiCreateEnseigne, [
            'query' => $param
        ]);
*/
        $responseMyEnseigne = $client->request('GET',
        $this->getParameter('API_URL').$this->apiCreateEnseigne, [
            'query' => $param
        ]);

        $content = $responseMyEnseigne->getContent();
        $content_array = json_decode($content, true);
        if(key_exists(0, $content_array['server_response']))
        {
            if($content_array['server_response'][0]['status'] == 1)
            {
                return $this->redirectToRoute('enseigne');
            }
        }

        return $this->redirectToRoute('enseigne-create',["error"=>true]);
    }


    /**
     * @Route("/update/{id}", name="enseigne-update")
     */
    public function update(Request $request,$id,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");

        $enseigneinfo = $this->ClientRequest($request,$client,'POST', 
        $this->getParameter('API_URL').$this->apiGetEnseigne, [
            'query' => [
                'idEnseigne'=>$id
            ]
        ]);
        //dd($enseigne);
        if(isset($enseigneinfo["server_responses"]) && $enseigneinfo["server_responses"]["founded"]==1){
            $enseigne = $enseigneinfo["server_responses"];
            return $this->render('enseigne/update.html.twig', [
                'controller_name' => 'EnseigneController',
                'user'=>$user,
                'enseigne'=>$enseigne
            ]);
        }else{
            return $this->redirectToRoute('enseigne');
        }
        
    }

    /**
     * @Route("/update-request", name="enseigne-update-request")
     */
    public function update_request(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");

        $data = $request->request->all();

        ///dd($data);
        $oldimg = $data["oldimg"]??"";
        $imgv=$request->files->get('file')??null;
        $img= null;
        if($imgv){
            $img=$this->rooturl.$this->uniquesave($request->files->get('file'));
        }else{
            $img=$oldimg;
        }

        $param = [
            "nom_enseigne"=>$data["nom_enseigne"],
            "phone"=>$data["phone"],
            "id"=>$data["id"],
            "logo"=>$img
        ];


           // dd($param);
        $result = $this->ClientRequest($request,$client,'GET', 
        $this->getParameter('API_URL').$this->apiUpdateEnseigneInfo, [
            'query' => $param
        ]);

       // dd($result);

        return $this->redirectToRoute('enseigne');
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

     public static function uniquesave($file,$chemin="profile"){
        $chemin = "uploads/".$chemin;
        $extension = $file->getClientOriginalExtension();
        $real_name = $file->getClientOriginalName();
        do{
            $name = uniqid('profile_').'.'.$extension;
        }while(file_exists($chemin.'/'.$name));
        $place = $chemin.'/'.$name;
        if($file->move($chemin,$name)){
            return $place;
        }
        return null;
    }
    public function ClientRequest($request,$client,$type,$url,$param){
        $tab=[];
        try{
            $response = $client->request($type,$url,$param);
             $content = $response->getContent(false);
    
             $content_array = json_decode($content, true);
             //dd($content_array,$response->getStatusCode());
             if($response->getStatusCode()==200){
                $tab=$content_array;
             }else{
                 $tab=[];
             }
  
             }catch(Exception $e){
                // dd($e->getMessage());
             }
             return $tab;
    }
    public function ClientRequestContent($request,$client,$type,$url,$param){
        $val="";
        try{
            $response = $client->request($type,$url,$param);
             $val = $response->getContent(false);
             }catch(Exception $e){
                // dd($e->getMessage());
             }
             return $val;
    }
}
