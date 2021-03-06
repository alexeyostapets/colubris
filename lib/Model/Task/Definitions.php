<?php
class Model_Task_Definitions extends Model_Auditable {
    public $table='task';

    function init(){
        parent::init();

        //$this->debug();

        $this->addField('name')->mandatory(true);
        $this->addField('priority')->setValueList(
            array(
                'low'=>'low',
                'normal'=>'normal',
                'high'=>'high',
            )
        )->defaultValue('normal');

        $this->addField('status')->setValueList($this->api->task_statuses)->defaultValue('unstarted')->sortable(true);
        $this->addField('type')->setValueList($this->api->task_types)->defaultValue('change request')->sortable(true);

        $this->addField('descr_original')->dataType('text');

        $this->addField('estimate')->dataType('money');
        //$this->addField('spent_time')->dataType('int');

        //$this->addField('deviation')->dataType('text');

        $this->addField('project_id')->refModel('Model_Project')->mandatory(true)->sortable(true);
        $this->addField('requirement_id')->refModel('Model_Requirement');
        //$this->addField('requester_id')->refModel('Model_User_Organisation');
        //$this->addField('assigned_id')->refModel('Model_User_Organisation');

        if($this->api->currentUser()->isClient()){
            /* Doesn't work with deleting tasks in CRUD
                        $j = $this->join('project.id','project_id','left','_p');
                        $j->addField('client_id','client_id');
                        $this->addCondition('client_id',$this->api->auth->model['client_id']);
            */
            $mp=$this->add('Model_Project');
            $mp->forClient();
            $projects_ids="0";
            foreach($mp->getRows() as $p){
                $projects_ids=$projects_ids.','.$p['id'];
            }
            $this->addCondition('project_id','in',$projects_ids);
        }

        if($this->api->currentUser()->isDeveloper()){
            $mp=$this->add('Model_Project');
            $mp->forDeveloper();
            $projects_ids="0";
            foreach($mp->getRows() as $p){
                $projects_ids=$projects_ids.','.$p['id'];
            }
            $this->addCondition('project_id','in',$projects_ids);
        }

        $this->addField('created_dts');
        $this->addField('updated_dts')->caption('Updated')->sortable(true);

        $this->addField('is_deleted')->type('boolean')->defaultValue('0');
        $this->addField('deleted_id')->refModel('Model_User');
        $this->addHook('beforeDelete', function($m){
            $m['deleted_id']=$m->api->currentUser()->get('id');
        });

        $this->addField('organisation_id')->refModel('Model_Organisation');
        $this->addCondition('organisation_id',$this->api->auth->model['organisation_id']);

        $this->addHook('beforeInsert', function($m,$q){
            $q->set('created_dts', $q->expr('now()'));
        });

        $this->addHook('beforeSave', function($m){
            $m['updated_dts']=date('Y-m-d G:i:s', time());
        });
        $this->addHook('afterSave', function($m){
            $m->api->mailer->task_status=$m['status'];
            $m->api->mailer->addReceiverByUserId($m->get('requester_id'),'mail_task_changes');
            $m->api->mailer->addReceiverByUserId($m->get('assigned_id'),'mail_task_changes');
            $m->api->mailer->sendMail('task_edit',array(
                'link'=>$m->api->siteURL().$m->api->url('/task',array('task_id'=>$m->get('id'),'colubris_task_view_view_crud_virtualpage'=>null)),
                'subject'=>'Task "'.substr($m->get('name'),0,25).'" has changes',
                'changer_part'=>$m->api->currentUser()->get('name').' has made changes in task "'.$m->get('name').'".',
            ));
        });
        $this->addHook('beforeDelete', function($m){
            $m->api->mailer->addReceiverByUserId($m->get('requester_id'),'mail_task_changes');
            $m->api->mailer->addReceiverByUserId($m->get('assigned_id'),'mail_task_changes');
            $m->api->mailer->sendMail('task_delete',array(
                'subject'=>'Task "'.substr($m->get('name'),0,25).'" deleted',
                'changer_part'=>$m->api->currentUser()->get('name').' has deleted task "'.$m->get('name').'".',
            ));
        });

        $this->setOrder('updated_dts',true);

        $this->addExpression('spent_time')->set(function($m,$q){
            return $q->dsql()
                ->table('task_time')
                ->field('sum(task_time.spent_time)')
                ->where('task_time.task_id',$q->getField('id'))
                ->where('task_time.remove_billing',false)
                ;
        });
    }

    function whatFieldsUserCanEdit($user) {
        if ($user->isAdmin()) {
            return array();
        } else if ($user->isManager()) {
            return array('name','descr_original','priority','type','status','estimate','requester_id','assigned_id');
        } else if ($user->isDeveloper()) {
            return array('name','descr_original','priority','type','status','estimate','requester_id','assigned_id');
        } else if ($user->isClient()) {
            return array('name','descr_original','priority','type','status');
        } else if ($user->isSales()) {
            return array('name','descr_original','priority','type','status','estimate');
        }
        throw $this->exception('Wrong role');
    }

    function whatFieldsUserCanSee($user,$quote=null) {
        if ($user->isAdmin()) {
            return array();
        } else if ($user->isManager()) {
            return array('name','priority','type','status','estimate','spent_time','requester','assigned');
        } else if ($user->isDeveloper()) {
            return array('name','priority','type','status','estimate','spent_time','requester','assigned');
        } else if ($user->isClient()) {
            if (is_object($quote) && $quote['show_time_to_client']) {
                return array('name','priority','type','status','estimate','spent_time');
            } else {
                return array('name','priority','type','status','estimate');
            }
        } else if ($user->isSales()) {
            return array('name','priority','type','status','estimate','spent_time','requester','assigned');
        }
        throw $this->exception('Wrong role');
    }
}
