<?php
namespace CsnUser\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use CsnUser\Entity\User; // only for the filters
use CsnUser\Form\LoginForm;
use CsnUser\Form\LoginFilter;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
		$em = $this->getEntityManager();
		$users = $em->getRepository('CsnUser\Entity\User')->findAll();

        return new ViewModel(array('users' => $users));
    }
	
    public function loginAction()
    {
		$form = new LoginForm();
		$form->get('submit')->setValue('Login');
		$messages = null;

		$request = $this->getRequest();
        if ($request->isPost()) {

			$form->setInputFilter(new LoginFilter($this->getServiceLocator()));
            $form->setData($request->getPost());
            if ($form->isValid()) {
				$data = $form->getData();			
				$authService = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');		
				$adapter = $authService->getAdapter();	
				$adapter->setIdentityValue($data['username']);
				$adapter->setCredentialValue($data['password']);
				$authResult = $authService->authenticate();
				if ($authResult->isValid()) {
					$identity = $authResult->getIdentity();
					$authService->getStorage()->write($identity);
					$time = 1209600; // 14 days = 1209600/3600 = 336 hours => 336/24

					if ($data['rememberme']) {
						$sessionManager = new \Zend\Session\SessionManager();
						$sessionManager->rememberMe($time);
					}
					//- return $this->redirect()->toRoute('home');
				}
				foreach ($authResult->getMessages() as $message) {
					$messages .= "$message\n"; 
				}	
			}
		}
		return new ViewModel(array(
			'error' => 'Your authentication credentials are not valid',
			'form'	=> $form,
			'messages' => $messages,
		));
    }
	
	public function logoutAction()
	{
		$auth = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');

		// @todo Set up the auth adapter, $authAdapter

		if ($auth->hasIdentity()) {
			$identity = $auth->getIdentity();
		}
		$auth->clearIdentity();
		$sessionManager = new \Zend\Session\SessionManager();
		$sessionManager->forgetMe();

		return $this->redirect()->toRoute('csn-user/default', array('controller' => 'index', 'action' => 'login'));
		
	}	
	
	// the use of controller plugin
	public function authTestAction()
	{
		if ($user = $this->identity()) { // controller plugin
			// someone is logged !
		} else {
			// not logged in
		}
	}
	
	/**             
	 * @var Doctrine\ORM\EntityManager
	 */                
	protected $em;
	 
	public function getEntityManager()
	{
		if (null === $this->em) {
			$this->em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
		}
		return $this->em;
	}
}