<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use MercurySeries\FlashyBundle\FlashyNotifier;


class AuthController extends AbstractController
{
    
    //atributes

    private $apiLoginUrl="login.php";
    private $apiCompteEcash="my_ecash_code.php";
    private $apiUpdateProfileInfo="update_profile_info.php";
    private $apiUpdateProfileEntreprise= "update_profil_entreprise.php";
    private $apiUpdatePassword = 'update_passe.php';
    private $apiStatistique='statistique.php';
    private $rooturl='https://my-net2all.online/public/';
    //private $rooturl='https://127.0.0.1:8082/';

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

        /**
         * 
         * COMPTE ECASH
         * 
         */

        $response = $client->request('GET', $this->getParameter('API_URL')."mynet2all/".$this->apiCompteEcash, [
            'query' => [
                'id' => $user['id_utilisateur'],
            ]
        ]);


    $content = $response->getContent(true);
    $content_array = json_decode($content, true);
    $code=$content_array["server_responses"]["code"]??null;
    //dd($code);
    $user["code_identification"]=$code;
    
    
    $session = $request->getSession();
    $session->set("currentuser",$user);

    /************************************************ */

        return $this->redirectToRoute('dashboard');
    }
    else
    {
        return $this->redirectToRoute('login',['error'=>1]);
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
     * @Route("/dashboard/", name="dashboard" )
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
        $response = $client->request('GET', $this->getParameter('API_URL')."mynet2all/".$this->apiStatistique, [
            'query' => [
                'id' => $user['id_utilisateur'],
            ]
        ]);


        $content = $response->getContent(true);
        $content_array = json_decode($content, true);
        $info = $code=$content_array["server_responses"];
    

        //dd($user);
        return $this->render('auth/dashboard.html.twig', [
            'controller_name' => 'AuthController',
            'user'=>$user,
            'info'=>$info

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

     /*****************************************************************************
      *
      *                         GESTION DU PROFIL
      * 
      *
      **************************************************************************/

    /**
     * @Route("/profile/", name="profile" )
     */
    public function profile(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");
        //dd($user);
        return $this->render('auth/profile.html.twig', [
            'controller_name' => 'AuthController',
            'user'=>$user,

        ]);
    }

    //Modification information personnel

    /**
     * @Route("/profile/update/info", name="profile-update-info" )
     */
    public function profile_update_info(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
        $session = $request->getSession();
        $user=$session->get("currentuser");
        $nom=$request->request->get("nom")??$user["nom"];
        $prenoms=$request->request->get("prenoms")??$user["prenoms"];
        $nom_entreprise=$request->request->get("nom_entreprise")??$user["nom_entreprise"]??"";
        //dd($user);
        $type=$user['type'];
        $param = [
            'id' => $user['id'],
            'nom'=> $nom,
            'prenoms'=> $prenoms,
            'nom_entreprise'=> $nom_entreprise,
            'type'=> $type
        ];
        //dd($param);
        $response = $client->request('POST', $this->getParameter('API_URL')."mynet2all/".$this->apiUpdateProfileInfo, [
            'query' => $param
        ]);

    $content = $response->getContent(true);
    $content_array = json_decode($content, true);
    //dd($content);
    $user["nom"]=$nom;
    $user["prenoms"]=$prenoms;
    if($type=="6"){
    $user["nom_entreprise"]=$nom_entreprise??"";
    }
    $session->set("currentuser",$user);

        return $this->redirectToRoute("profile");
    }


    //update profile

        /**
     * @Route("/profile/update/photo", name="profile-update-photo" )
     */
    public function profile_update_photo(Request $request,HttpClientInterface $client): Response
    {
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
         $session = $request->getSession();
         $user=$session->get("currentuser");
         $url_image=$this->rooturl.$this->uniquesave($request->files->get('file'));
         $params= [
             "id"=>$user["id"],
             "url_image"=>$url_image
         ];
         //dd($params);
        
         $response = $client->request('POST', $this->getParameter('API_URL').$this->apiUpdateProfileEntreprise, [
             'query' => $params
         ]);
         
  
     $content = $response->getContent();//dd($content,$user->getId());
     $content_array = json_decode($content, true);
 
    $user["url_image"]=$url_image;
    
    $session->set("currentuser",$user);

        return $this->redirectToRoute("profile");
    }
//update password
   /**
     * @Route("/profile/update/password", name="profile-update-password" )
     */
    public function profile_update_password(Request $request,FlashyNotifier $flashy,HttpClientInterface $client): Response
    {
        
     
        
        $checkstatus=$this->check_authentificated($request);
        if(!$checkstatus["status"]){
            return $this->redirectToRoute('login');
         }
         $session = $request->getSession();
         $user=$session->get("currentuser");
         
         $old_pwd = $request->request->get("old_pwd");
         $password = $request->request->get("password");
         $cpassword = $request->request->get("cpassword");

        /* if($password != $cpassword){
            $flashy->error('Les mots de passe ne correspondent pas');
            return $this->redirectToRoute("profile");
         }*/

        $param =[
            'old_passe' => $old_pwd,
            'new_passe' => $password,
            'id_user' => $user['id_utilisateur']
        ];
        //dd($param);

         $response = $client->request('POST', 
        $this->getParameter('API_URL').$this->apiUpdatePassword, [
        'body' => $param
    ]);

    $content = $response->getContent();
    $content_array = json_decode($content, true);
    //dd($param,$content_array);
    if($content_array['server_response'][0]['status'] == 1)
                        {
                            $user["password"]=$password;
    
                            $session->set("currentuser",$user);

                            $flashy->success('Mot de passe bien mis à jour');
                        }
                        else{
                            $flashy->error('Erreur, mot de passe ne peut être mis à jour');
                        
                        }
                

        return $this->redirectToRoute("profile");
    }

    /** upload script */

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
    }