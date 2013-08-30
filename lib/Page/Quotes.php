<?php
/**
 * Created by JetBrains PhpStorm.
 * User: vadym
 * Date: 8/29/13
 * Time: 1:02 PM
 * To change this template use File | Settings | File Templates.
 */
class Page_Quotes extends Page {
    public $quote;
    function init() {
        parent::init();
        $this->quote = $this->add('Model_Quote');//->debug();

        if ($this->api->currentUser()->isClient()) {
            // show only client's quotes
            $pr = $this->quote->join('project','project_id','left','_pr');
            $pr->addField('pr_client_id','client_id');
            $this->quote->addCondition('pr_client_id',$this->api->auth->model['client_id']);
        }

        if ($this->api->currentUser()->isDeveloper()) {
            // developer do not see not well prepared (quotation_requested status) and finished projects
            $this->quote->addCondition('status',array(
                'estimate_needed','not_estimated','estimated','estimation_approved'
            ));
        }
    }
    function page_index() {
        $this->addBreadCrumb($this);
        $this->add('H1')->set('Quotes');
        $this->addRequestForQuotationButton($this);
        $this->addQuotesCRUD($this);
    }

    function addBreadCrumb($view) {
        $view->add('x_bread_crumb/View_BC',array(
            'routes' => array(
                0 => array(
                    'name' => 'Home',
                ),
                1 => array(
                    'name' => 'Quotes',
                    'url' => $this->role.'/quotes',
                ),
            )
        ));
    }

    function addRequestForQuotationButton($view) {
        if ($this->api->currentUser()->canSendRequestForQuotation()) {
            $b = $view->add('Button')->set('Request For Quotation');
            $b->addStyle('margin-bottom','10px');
            $b->js('click', array(
                $this->js()->univ()->redirect($this->api->url($this->role.'/quotes/rfq'))
            ));
        }
    }

    function addQuotesCRUD($view) {
        $user = $this->api->currentUser();
        $cr = $view->add('CRUD', array(
            'grid_class'      => 'Grid_Quotes',
            'allow_add'       => false,
            'allow_edit'      => $this->quote->canUserEditQuote($user),
            'allow_del'       => $this->quote->canUserDeleteQuote($user),
            'role'            => $this->role,
            'allowed_actions' => $this->quote->userAllowedActions($user),
        ));

        $cr->setModel(
            $this->quote,
            $this->quote->whatQuoteFieldsUserCanEdit($user),
            $this->quote->whatQuoteFieldsUserCanSee($user)
        );
    }
}