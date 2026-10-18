<?php
namespace App\Controllers;

class Auth extends BaseController
{
    protected $db;

    public function __construct()
    {
        // Instantiates the native database service connection layer
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Intercepts active sessions to route users away from log-in prompt walls
        if (session()->get('user_id')) {
            return redirect()->to('dashboard');
        }
        return view('login_view');
    }

    public function login()
    {
        $session = session();
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Clean query processing using Query Builder binding structures
        $query = $this->db->query("SELECT * FROM users WHERE email = ?", [$email]);
        $user = $query->getRowArray();

        if ($user) {
            if (password_verify($password, $user['password'])) {
                $session->set([
                    'user_id'  => $user['id'],
                    'fullname' => $user['fullname'],
                    'email'    => $user['email']
                ]);
                return redirect()->to('dashboard');
            } else {
                $session->setFlashdata('error', 'Wrong password! Try again.');
                return redirect()->back();
            }
        } else {
            $session->setFlashdata('error', 'Email not found! Please register first.');
            return redirect()->back();
        }
    }
}
