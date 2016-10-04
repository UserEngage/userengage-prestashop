<?php

/*
 * 2015 UserEngage.io
 */

if (!defined('_PS_VERSION_'))
    exit;

class userengage extends Module {

    public function __construct() {
        $this->name = 'userengage';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'UserEngage.io';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('UserEngage');
        $this->description = $this->l('UserEngage integration with PrestaShop. Start speak and engage your Customers Visitors.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall UserEngage?');

        if (!Configuration::get('USERENGAGE'))
            $this->warning = $this->l('No name provided');
    }

    public function install() {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!parent::install() ||
                !$this->registerHook('header') || !$this->registerHook('footer') || !$this->registerHook('createAccount') || !$this->registerHook('newOrder') ||
                !Configuration::updateValue('USERENGAGE', 'UserEngage ApiKey')
        )
            return false;
        $this->_moveModulePosition(1, 'createAccount');
        return true;
    }

    public function uninstall() {
        if (!parent::uninstall() ||
                !Configuration::deleteByName('USERENGAGE')
        )
            return false;

        return true;
    }

    public function hookHeader() {
        $loggedinfo = Context::getContext()->customer->isLogged();
        $widget = '<script type="text/javascript">';
       
        if ($loggedinfo) {
            
            $firstname = Context::getContext()->customer->firstname;
             $lastname = Context::getContext()->customer->lastname;
             $email = Context::getContext()->customer->email;
             $gender_id = Context::getContext()->customer->id_gender;
             $gender = ($gender_id == 1 ? 'male' : 'female');
             $birthday = Context::getContext()->customer->birthday;
             $widget .= '
                        window.civchat = {
                          apiKey: "' . Tools::safeOutput(Configuration::get('UE_APIKEY')) . '",
                          name: "' . $firstname . ' ' . $lastname . '",
                          email: "' . $email . '",
                          gender: "'.$gender.'",
                          birthday: "'.$birthday.'"
                    }';
        } else {
            
        $firstname = Context::getContext()->customer->firstname;
        $lastname = Context::getContext()->customer->lastname;
        $email = Context::getContext()->customer->email;
        $widget .= '
        window.civchat = {
          apiKey: "' . Tools::safeOutput(Configuration::get('UE_APIKEY')) . '",
          name: "' . $firstname . ' ' . $lastname . '",
          email: "' . $email . '"
        }';
        }
            $key = Tools::getValue('UE_APIKEY');

            $widget .= '</script>';
            $widget .= '<script src="https://widget.userengage.io/widget.js"></script>';
            
        return $widget;
    }

    public function hookFooter() {

       
        if ($this->context->cookie->registration == 1) {

            $firstname = $this->context->cookie->firstnameUE;
            $lastname = $this->context->cookie->lastnameUE;
            $email = $this->context->cookie->emailUE;
            $birthday = $this->context->cookie->birthdayUE;

            $register = "userengage('event.UserRegister', {'firstname': '" . $firstname . "','lastname': '" . $lastname . "','email':'" . $email . "', 'birthday':'" . $birthday . "'})";
            $this->context->cookie->__unset('registration');
            $this->context->cookie->__unset('firstnameUE');
            $this->context->cookie->__unset('lastnameUE');
            $this->context->cookie->__unset('emailUE');
            $this->context->cookie->__unset('birthdayUE');
            $this->smarty->assign('register', $register);

            $html = $this->display(__FILE__, 'views/userregister.tpl');
            
        } elseif ($_GET["id_cart"] && $_GET["id_order"] && !$this->context->cookie->sendOrder) {
           
            $orderId = $_GET["id_order"];
            $orderData = new Order($_GET["id_order"]);
            $payment = $orderData->payment;
            $price = number_format($orderData->total_paid_tax_incl, 2, '.', '');
            $dateAdd = $orderData->date_add;
            $this->context->cookie->sendOrder = 1;
            

            $order = "userengage('event.NewOrder', {'orderId': '" . $orderId . "','payment': '" . $payment . "','price':'" . $price . "', 'date':'" . $dateAdd . "'})";



            $this->smarty->assign('neworder', $order);
            $html = $this->display(__FILE__, 'views/neworder.tpl');
        } else {
            $this->context->cookie->__unset('sendOrder');
            $this->smarty->assign('scriptpath', '/modules/userengage/assets/ajaxcart-override.js');
            $html = $this->display(__FILE__, 'views/userengage.tpl');
        }
        return $html;
    }

    function hookCreateAccount($params) {
        $custInfo = $params['_POST'];
        if (empty($custInfo))
            return false;
        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $country_name = Country::getNameById($this->context->language->id, Configuration::get('PS_COUNTRY_DEFAULT'));
        } else {
            global $cookie;
            $country_name = Country::getNameById(intval($cookie->id_lang), intval($custInfo['id_country']));
        }


        if ($custInfo) {
            $firstname = $custInfo['firstname'];
            $lastname = $custInfo['lastname'];
            $email = $custInfo['email'];
            $country = $custInfo['country'];
            $birthday = $custInfo['months'] . '-' . $custInfo['days'] . '-' . $custInfo['years'];
            $this->context->cookie->firstnameUE = $firstname;
            $this->context->cookie->lastnameUE = $lastname;
            $this->context->cookie->emailUE = $email;
            $this->context->cookie->countryUE = $country;
            $this->context->cookie->birthdayUE = $birthday;
            $this->context->cookie->registration = 1;
        }
    }

    public function displayForm() {
        $ue = new HelperForm();
        $ue->module = $this;
        $ue->name_controller = $this->name;
        $ue->token = Tools::getAdminTokenLite('AdminModules');
        $ue->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $ue->title = $this->displayName;
        $ue->show_toolbar = true;
        $ue->toolbar_scroll = true;
        $ue->submit_action = 'submit' . $this->name;
        $ue->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('UserEngage Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Your Api Key:'),
                    'desc' => $this->l('Please enter your application key which has been sent to your email address. The api key is a 64 letter and number key.'),
                    'name' => 'UE_APIKEY',
                    'required' => true,
                    'hint' => $this->l('This information is available in your UserEngage account')
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ),
            'buttons' => array(
                array(
                    'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                    'title' => $this->l('Back to list'),
                    'icon' => 'process-icon-back'
                )
            )
        );
        $ue->fields_value['UE_APIKEY'] = Configuration::get('UE_APIKEY');
        return $ue->generateForm($fields_form);
    }

    private function _moveModulePosition($position, $hookname) {
        if (_PS_VERSION_ < '1.5') {
            $hookID = (int) Hook::get($hookname);
        } else {
            $hookID = (int) Hook::getIdByName($hookname);
        }
        $moduleInstance = Module::getInstanceByName($this->name);
        if (_PS_VERSION_ < '1.5') {
            $moduleInfo = Hook::getModuleFromHook($hookID, $moduleInstance->id);
        } else {
            $moduleInfo = Hook::getModulesFromHook($hookID, $moduleInstance->id);
        }
        if (_PS_VERSION_ < '1.5') {
            if ((int) $moduleInfo['position'] > (int) $position) {
                return $moduleInstance->updatePosition($hookID, 0, (int) $position);
            } else {
                return $moduleInstance->updatePosition($hookID, 1, (int) $position);
            }
        } else {
            if ((int) $moduleInfo['m.position'] > (int) $position) {
                return $moduleInstance->updatePosition($hookID, 1, (int) $position);
            } else {
                return $moduleInstance->updatePosition($hookID, 0, (int) $position);
            }
        }
    }

    public function getContent() {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $ue_apikey = Tools::getValue('UE_APIKEY');
            if (!empty($ue_apikey)) {
                Configuration::updateValue('UE_APIKEY', $ue_apikey);
                $output .= $this->displayConfirmation($this->l('UserEngage ApiKey updated successfully.'));
            }
        }
        return $output .= $this->displayForm();
    }

}
