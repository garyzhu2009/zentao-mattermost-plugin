   public function buildData($objectType, $objectID, $actionType, $actionID, $webhook)
    {
        /* Validate data. */
        if(!isset($this->lang->action->label)) $this->loadModel('action');
        if(!isset($this->lang->action->label->$actionType)) return false;
        if(empty($this->config->objectTables[$objectType])) return false;
        $action = $this->dao->select('*')->from(TABLE_ACTION)->where('id')->eq($actionID)->fetch();

        if($webhook->products)
        {
            $webhookProducts = explode(',', trim($webhook->products, ','));
            $actionProduct   = explode(',', trim($action->product, ','));
            $intersect       = array_intersect($webhookProducts, $actionProduct);
            if(!$intersect) return false;
        }
        if($webhook->projects)
        {
            if(strpos(",$webhook->projects,", ",$action->project,") === false) return false;
        }

        static $users = array();
        if(empty($users)) $users = $this->loadModel('user')->getList();

        $object   = $this->dao->select('*')->from($this->config->objectTables[$objectType])->where('id')->eq($objectID)->fetch();



        $field    = $this->config->action->objectNameFields[$objectType];
        $host     = empty($webhook->domain) ? common::getSysURL() : $webhook->domain;
        $viewLink = $this->getViewLink($objectType, $objectID);
        $title    = $this->app->user->realname . $this->lang->action->label->$actionType . $this->lang->action->objectTypes[$objectType];
        $text     = $title . ' ' . "[#{$objectID}::{$object->$field}](" . $host . $viewLink . ")";

        $mobile = '';
        $email  = '';
        if(in_array($objectType, $this->config->webhook->needAssignTypes) && !empty($object->assignedTo))
        {
            foreach($users as $user)
            {
                if($user->account == $object->assignedTo)
                {
                    $mobile = $user->mobile;
                    $email  = $user->email;
                    break;
                }
            }
        }
        $action->text = $text;


        if($webhook->type == 'dingding')
        {
            $data = $this->getDingdingData($title, $text, $mobile);
        }
        elseif($webhook->type == 'bearychat')
        {
            $data = $this->getBearychatData($text, $mobile, $email, $objectType, $objectID);
        }
        else
        {
            $data = new stdclass();
            foreach(explode(',', $webhook->params) as $param) $data->$param = $action->$param;

            /***
              * BEGIN add by zhuj,20190321
              **/            
            if (empty($object->assignedTo))                 return false;           //如果没有指派人，不通知
            //if ($object->assignedTo == $action->actor)      return false;           //如果指派人与操作人为同一个人，不通知   (暂时不限制)       
            //Todo：指派人会不会有多个？  ==貌似禅道里只会记录第一个人
            //查询指派人查询禅道中配置的邮箱地址
            $tmpUser   = $this->dao->select('*')->from('zt_user')->where('account')->eq($object->assignedTo)->fetch();
            if (empty($tmpUser->email))                     return false;           //如果邮箱地址为空，不通知
            //根据邮箱地址获取MatterMost中的用户Username
            $tmpUser2   = $this->dao->select('*')->from('mm_users')->where('Email')->eq($tmpUser->email)->andWhere('DeleteAt')->eq('0')->fetch();
            if (empty($tmpUser2->Username))                 return false;           //如果没有找到mattermost用户，不通知
            $data->channel = "@" . $tmpUser2->Username;
            $data->username = "禅道(大数据)";
            $zjTmp = array(
                    "fallback"  =>  "禅道(大数据)新任务通知",
                    "color"     =>  "#FF8000",
                    "title"     =>  "新任务通知：",
                    //"title_link"   =>  $host,
                    "text"      =>  $text );
            $data->attachments = array('0'=>$zjTmp);
            $data->text = '';
            /***
              * END   add by zhuj
              **/


        }


        return helper::jsonEncode($data);
    }
