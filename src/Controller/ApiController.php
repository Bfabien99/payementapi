<?php

namespace App\Controller;

use App\Entity\Customers;
use App\Entity\Transaction;
use App\Repository\CustomersRepository;
use App\Service\JWT;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'app.api')]
class ApiController extends AbstractController
{
    private $manager;
    private $jwt;
    public function __construct(ManagerRegistry $manager, JWT $jwt)
    {
        $this->manager = $manager;
        $this->jwt = $jwt;
    }

    #[Route('/', name: 'app.api.index', methods: ['POST'])]
    public function index(): JsonResponse
    {
        $success = true;
        $message = "Payment Api v1";

        return $this->json([
            'success' => $success,
            'message' => $message,
            'author' => "bfabien99"
        ]);
    }

    #[Route('/register', name: 'app.api.userregister', methods: ['POST'])]
    public function userRegister(Request $request): JsonResponse
    {

        $success = false;
        $message = "";
        $errors = false;
        $require_params = ['name', 'firstname', 'email', 'phone', 'password'];
        $parameters = json_decode($request->getContent(), true);

        if ($parameters) {
            foreach ($require_params as $value) {
                if (!array_key_exists($value, $parameters)) {
                    $errors[] = "$value must be set.";
                    $message = "Required field missing";
                } elseif (empty($parameters[$value])) {
                    $errors[] = "$value must not be empty.";
                    $message = "Empty field found.";
                }
            }
        } else {
            $errors[] = "Request body can't be empty";
            $message = "Request body not found.";
        }

        if (!$errors) {
            $isCustomers = $this->manager->getRepository(Customers::class)->findBy(["phone" => $parameters["phone"]]);

            if (!$isCustomers) {
                $customer = new Customers();
                $customer->setName($parameters['name']);
                $customer->setFirstname($parameters['firstname']);
                $customer->setEmail($parameters['email']);
                $customer->setPhone($parameters['phone']);
                $customer->setPassword($parameters['password']);
                $customer->setBalance(0);

                $this->manager->getManager()->persist($customer);
                $this->manager->getManager()->flush();                

                $success = true;
                $message = "Registered successfully";
            }else{
                $errors[] = "Phone already exist!";
                $message = "Canceled registration";
            }
        }

        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'results' => isset($customer) ? $customer->returnArray() : []
        ]);
    }

    #[Route('/login', name: 'app.api.userlogin', methods: ['POST'])]
    public function userLogin(Request $request): JsonResponse
    {
        $token = false;
        $success = false;
        $message = "";
        $errors = false;
        $require_params = ["phone", "password"];
        $parameters = json_decode($request->getContent(), true);

        if ($parameters) {
            foreach ($require_params as $value) {
                if (!array_key_exists($value, $parameters)) {
                    $errors[] = "$value must be set.";
                    $message = "Required field missing";
                } elseif (empty($parameters[$value])) {
                    $errors[] = "$value must not be empty.";
                    $message = "Empty field found.";
                }
            }
        } else {
            $errors[] = "Request body can't be empty";
            $message = "Request body not found.";
        }

        if (!$errors) {
            $customer = $this->manager->getRepository(Customers::class)->findOneBy(["phone" => $parameters["phone"],"password" => md5($parameters["password"])]);

            if($customer){
                $payload = [
                "customer_id" => $customer->getId(),
                'iat' => time(),
                'exp' => time() + (30 * 60),
            ];

            $token = $this->jwt->encode($payload, "SECRETE_KEY");
            $message = "Login successfully";
            $success = true;
            }else{
                $errors[] = "phone or password is not correct.";
                $message = "Credentials error.";
            }
            
        }
        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'token' => $token
        ]);
    }

    #[Route('/account', name: 'app.api.useraccount', methods: ['POST'])]
    public function userAccount(Request $request): JsonResponse
    {
        $success = false;
        $message = "";
        $errors = false;
        $parameters = json_decode($request->getContent(), true);

        if ($parameters) {
            if (!array_key_exists("token", $parameters)) {
                $errors[] = "token must be set.";
                $message = "Required field missing";
            } elseif (empty($parameters["token"])) {
                $errors[] = "token must not be empty.";
                $message = "Empty field found.";
            } else {
                try {
                    $payload = $this->jwt->decode($parameters["token"], "SECRETE_KEY", ['HS256']);
                    $customer = $this->manager->getRepository(Customers::class)->find($payload->customer_id);
                    if($customer){
                        $success = true;
                        $message = "Access granted";
                    }else{
                        $message = "Invalid Token.";
                        $errors[] = "This token is corrupted"; 
                    }
                    
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $message = "Token error.";
                }
            }
        } else {
            $errors[] = "Request body can't be empty";
            $message = "Request body not found.";
        }

        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'results' => isset($customer) ? $customer->returnArray() : []
        ]);
    }

    #[Route('/account/transaction/{id}', name: 'app.api.usertransaction', methods: ['GET'])]
    public function getUserTransaction($id){
        $transactions = $this->manager->getRepository(Transaction::class)->findBy(["sender_id" => $id]);

        return $this->json([
            'success' => $transactions
        ]);
    }

    #[Route('/account/deposite', name: 'app.api.userdeposite', methods: ['POST'])]
    public function userDeposite(Request $request): JsonResponse
    {
        $success = false;
        $message = "";
        $errors = false;
        $require_params = ["token","amount","receiver_phone"];
        $parameters = json_decode($request->getContent(), true);

        if ($parameters) {
            foreach ($require_params as $value) {
                if (!array_key_exists($value, $parameters)) {
                    $errors[] = "$value must be set.";
                    $message = "Required field missing";
                } elseif (empty($parameters[$value])) {
                    $errors[] = "$value must not be empty.";
                    $message = "Empty field found.";
                }
            } 
            
            if(!$errors) {
                try {
                    $payload = $this->jwt->decode($parameters["token"], "SECRETE_KEY", ['HS256']);
                    $customer = $this->manager->getRepository(Customers::class)->find($payload->customer_id);
                    $receiver = $this->manager->getRepository(Customers::class)->findOneBy(["phone" => $parameters["receiver_phone"]]);

                    if($customer && $receiver){
                        $transaction = new Transaction();
                        if ($customer->getBalance() >= $parameters["amount"]) {
                            $customer->setBalance(-$parameters["amount"]);
                            $receiver->setBalance($parameters["amount"]);

                            $transaction->setSenderId($customer);
                            $transaction->setReceiverPhone($receiver->getPhone());
                            $transaction->setType("deposite");
                            $transaction->setAmount($parameters["amount"]);

                            $this->manager->getManager()->persist($customer);
                            $this->manager->getManager()->persist($transaction);
                            $this->manager->getManager()->persist($receiver);

                            $this->manager->getManager()->flush();

                            $success = true;
                            $message = "Deposite successfuly!";
                        }else{
                            $message = "Insuffisant balance!";
                            $errors[] = "Your balance is low than the amount, please refill your account!";
                        }
                        
                    }elseif(!$customer){
                        $message = "Invalid Token.";
                        $errors[] = "This token is corrupted"; 
                    }
                    else{
                        $message = "Receiver phone error";
                        $errors[] = "Receiver should register first or you put a wrong number";
                    }
                    
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $message = "Token error.";
                }
            }

        } else {
            $errors[] = "Request body can't be empty";
            $message = "Request body not found.";
        }

        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
            'results' => [
                "customer" => isset($customer) ? $customer->returnArray() : [],
                "receiver" => isset($receiver) ? $receiver->returnArray() : [],
                "transaction" => isset($transaction) ? $transaction->returnArray() : []
            ]
        ]);
    }

    #[Route('/account/deposites', name: 'app.api.useralldeposite', methods: ['GET'])]
    public function getAllUserDeposite(Request $request): JsonResponse
    {
        $success = false;
        $message = "";
        $errors = false;
        $parameters = json_decode($request->getContent(), true);

        if ($parameters) {
            if (!array_key_exists("token", $parameters)) {
                $errors[] = "token must be set.";
                $message = "Required field missing";
            } elseif (empty($parameters["token"])) {
                $errors[] = "token must not be empty.";
                $message = "Empty field found.";
            } else {
                try {
                    $payload = $this->jwt->decode($parameters["token"], "SECRETE_KEY", ['HS256']);
                    $customer = $this->manager->getRepository(Customers::class)->find($payload->customer_id);
                    if($customer){
                        $success = true;
                        $message = "Access granted";
                    }else{
                        $message = "Invalid Token.";
                        $errors[] = "This token is corrupted"; 
                    }
                    
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $message = "Token error.";
                }
            }
        } else {
            $errors[] = "Request body can't be empty";
            $message = "Request body not found.";
        }

        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    #[Route('/account/deposite/{code}', name: 'app.api.useronedeposite', methods: ['GET'])]
    public function getUserDeposite(Request $request,$code): JsonResponse
    {
        $success = false;
        $message = $code;
        $errors = false;
        $parameters = json_decode($request->getContent(), true);

        if ($parameters) {
            if (!array_key_exists("token", $parameters)) {
                $errors[] = "token must be set.";
                $message = "Required field missing";
            } elseif (empty($parameters["token"])) {
                $errors[] = "token must not be empty.";
                $message = "Empty field found.";
            } else {
                try {
                    $payload = $this->jwt->decode($parameters["token"], "SECRETE_KEY", ['HS256']);
                    $customer = $this->manager->getRepository(Customers::class)->find($payload->customer_id);
                    if($customer){
                        $success = true;
                        $message = "Access granted";
                    }else{
                        $message = "Invalid Token.";
                        $errors[] = "This token is corrupted"; 
                    }
                    
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $message = "Token error.";
                }
            }
        } else {
            $errors[] = "Request body can't be empty";
            $message = "Request body not found.";
        }
        
        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    #[Route('/account/withdraw', name: 'app.api.userwithdraw', methods: ['POST'])]
    public function userWithdraw(Request $request): JsonResponse
    {
        $success = false;
        $message = "";
        $errors = false;
        $parameters = json_decode($request->getContent(), true);

        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    #[Route('/account/withdraws', name: 'app.api.userallwithdraw', methods: ['GET'])]
    public function getAllUserWithdraw(Request $request): JsonResponse
    {
        $success = false;
        $message = "";
        $errors = false;
        $parameters = json_decode($request->getContent(), true);

        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    #[Route('/account/withdraw/{code}', name: 'app.api.useronewithdraw', methods: ['GET'])]
    public function getUserWithdraw(Request $request, $code): JsonResponse
    {
        $success = false;
        $message = "";
        $errors = false;
        $parameters = json_decode($request->getContent(), true);

        if ($parameters) {
            if (!array_key_exists("token", $parameters)) {
                $errors[] = "token must be set.";
                $message = "Required field missing";
            } elseif (empty($parameters["token"])) {
                $errors[] = "token must not be empty.";
                $message = "Empty field found.";
            } else {
                try {
                    $payload = $this->jwt->decode($parameters["token"], "SECRETE_KEY", ['HS256']);
                    $customer = $this->manager->getRepository(Customers::class)->find($payload->customer_id);
                    if($customer){
                        $success = true;
                        $message = "Access granted";
                    }else{
                        $message = "Invalid Token.";
                        $errors[] = "This token is corrupted"; 
                    }
                    
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    $message = "Token error.";
                }
            }
        } else {
            $errors[] = "Request body can't be empty";
            $message = "Request body not found.";
        }
        return $this->json([
            'success' => $success,
            'message' => $message,
            'errors' => $errors,
        ]);
    }
}
