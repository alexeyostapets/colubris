<?php
class View_ReportsSwitcher extends View {
    function init(){
        parent::init();

        $v=$this->add('View')->setClass('right');
        
        $f=$v->add('Form');
        $f->addClass('horizontal switcher');
        // Project
        if( ($this->api->auth->model['is_developer']) && (!$this->api->auth->model['is_manager']) ){
        	$mp=$this->add('Model_Project_Participant');
        }else{
        	$mp=$this->add('Model_Project');
        }
        $projects=$mp->getRows();
        if($_GET['project_id']){
        	$this->api->memorize('project_id',$_GET['project_id']);
        	$this->api->memorize('quote_id',0);
        }elseif(!$this->api->recall('project_id')){
        	if(count($projects)>0){
        		$this->api->memorize('project_id',$projects[0]['id']);
        	}
        }
        $p_arr=array();
        foreach ($projects as $p){
        	$p_arr[$p['id']]=$p['name'];
        }
        $fp=$f->addField('dropdown','project');
        $fp->setValueList($p_arr);
        $fp->set($this->api->recall('project_id'));

        // Quote
		$mq=$this->add('Model_Quote');
		$mq->addCondition('status','estimation_approved');
		$mq->addCondition('project_id',$this->api->recall('project_id'));
		if($_GET['quote_id']!==null){
			$check=$mq->tryLoad($_GET['quote_id']);
			if(!$check->loaded()){
				$_GET['quote_id']=0;
			}
		}
		$q_arr=$mq->getRows();
		$qn_arr['0']='all';
		foreach($q_arr as $q){
			$qn_arr[$q['id']]=$q['name'];
		}
		if($_GET['quote_id']!==null){
			$this->api->memorize('quote_id',$_GET['quote_id']);
		}
		$fq=$f->addField('dropdown','quote');
		$fq->setValueList($qn_arr);
		$fq->set($this->api->recall('quote_id'));
		
        // Assigned_to
        $ma=$this->add('Model_User')->setOrder('name');
        $a_arr=$ma->getRows();
        $u_arr['0']='all';
        foreach($a_arr as $a){
            $u_arr[$a['id']]=$a['name'];
        }
        if($_GET['assigned_id']!==null){
            $this->api->memorize('assigned_id',$_GET['assigned_id']);
        }
        $fa=$f->addField('dropdown','assigned_id','Assigned');
        $fa->setValueList($u_arr);
        $fa->set($this->api->recall('assigned_id'));

        // Date from
        if($_GET['date_from']!==null){
            $this->api->memorize('date_from',$_GET['date_from']);
        }
        $fdate_from=$f->addField('DatePicker','date_from','From');
        $fdate_from->set($this->api->recall('date_from'));

        // Date to
        if($_GET['date_to']!==null){
            $this->api->memorize('date_to',$_GET['date_to']);
        }
        $fdate_to=$f->addField('DatePicker','date_to','To');
        $fdate_to->set($this->api->recall('date_to'));


        $js_arr=array(
            $this->api->url(),
            'project_id'=>$fp->js()->val(),
            'quote_id'=>$fq->js()->val(),
            'assigned_id'=>$fa->js()->val(),
            'date_from'=>$fdate_from->js()->val(),
            'date_to'=>$fdate_to->js()->val(),
        );

        $fq->js('change')->univ()->location($js_arr);
        $fp->js('change')->univ()->location($js_arr);
        $fa->js('change')->univ()->location($js_arr);
        $fdate_from->js('change')->univ()->location($js_arr);
        $fdate_to->js('change')->univ()->location($js_arr);

        $v=$this->add('View')->setClass('clear');
    }
}