<?php
class Model_Log extends Model_Table {
	public $table="log";
	function init(){
		parent::init();
		
		$this->addField('new_data');
		$this->addField('changed_fields');
		$this->addField('class');
		$this->addField('rec_id');
		$this->addField('created_at');
		$this->hasOne('User','user_id','email');
        $this->addField('organisation_id')->refModel('Model_Organisation');
        $this->addCondition('organisation_id',$this->api->auth->model['organisation_id']);

        $this->addHook('beforeInsert',function($m,$q){
            $q->set('created_at',$q->expr('now()'));
        });
		
		$this->addHook('beforeSave',function($m){
		    $m['user_id']=$m->api->auth->model['id'];
		});
		
	}

}