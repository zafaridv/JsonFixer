<?php
/**
 * JsonFixer 
 * @author Mohammad Eesa Zafari
 */
class JsonFixer {
    private $txt;
    private $result;
    function __construct($txt){
        $this->result = [];

        $txt = preg_replace([
            '/\s*,\s*/',
            '/\s*:\s*/',
            '/\s*{\s*/',
            '/\s*}\s*/',
            '/[\n\t]\s*\'/',
            '/[\n\t]\s*"/',
            '/[\n\t]\s*\[\s*/',
            '/[\n\t]\s*\]\s*/',
            '/\s*\n\s*/',
            '/\s*\t\s*/'
        ],[
            ',',
            ':',
            '{',
            '}',
            "'",
            '"',
            '[',
            ']',
            '\\n',
            '\\t'
        ],$txt);
        $this->txt = $txt;
        if($txt[0]=='{'){
            $this->result = $this->startFixer('object');
        }else{
            $this->result = $this->startFixer('array');
        }
    }
    public function getResult($resultType='json'){
        if($resultType=='array'){
            return $this->result;
        }else{
            return json_encode($this->result);
        }
    }

    function startFixer($type='object'){
        $temp = [];

        //remove {} or []
        $this->txt = substr($this->txt,1,strlen($this->txt)-2);        
        
        if($type == 'object'){
            $temp[$this->findIndex()] = $this->findValue();
            while(strlen($this->txt)>1){
                $temp[$this->findIndex()] = $this->findValue();
            }
            return $temp;
        }else{
            //check for {
            if($this->txt[0]=='{'){
                $this->txt = substr($this->txt,1,-1);
                $objects = explode('},{',$this->txt);
                foreach($objects as $obj){
                    $this->txt = '{'.$obj.'}';
                    $temp[] = $this->startFixer('object');
                }
            }else{
                $temp[] = $this->findValue();
                while(strlen($this->txt)>0){
                    $temp[] = $this->findValue();
                }
            }
        }
        return $temp;
    }

    function findIndex(){
        $i = 0;
        //find the index delimiter ' or "
        if($this->txt[$i] == "'"){
            $regex = '/\':[\'"{\[]/';
        }else{
            $regex = '/":[\'"{\[]/';
        }

        //find closing
        preg_match($regex,$this->txt,$find,PREG_OFFSET_CAPTURE);
        $find = $find[0];
        $index = substr($this->txt,$i+1,$find[1]-($i+1));
        $this->txt = substr($this->txt,$find[1]+strlen($find[0])-1);
        return $index;
    }

    function findValue(){
        switch($this->txt[0]){
            case '"':
                $regex = '/",[\'"]|"\z/';
            break;
            case "'":
                $regex = '/\',[\'"]|\'\z/';
            break;
            case '[':
                //find closing
                preg_match('/[\'"}]\][,\'"}]?/',$this->txt,$find,PREG_OFFSET_CAPTURE);
                $find = $find[0];
                $remaining = substr($this->txt,($find[1]+strlen($find[0])),strlen($this->txt) );
                $this->txt = substr($this->txt,0,$find[1]+2);
                $temp = $this->startFixer('array');
                $this->txt = $remaining;
                return $temp;
            break;
            case '{':
                
                //find closing
                preg_match('/[\'"]},?/',$this->txt,$find,PREG_OFFSET_CAPTURE);
                $find = $find[0];
                $remaining = substr($this->txt,($find[1]+strlen($find[0])),strlen($this->txt) );
                $this->txt = substr($this->txt,0,$find[1]+2);
                $temp = $this->startFixer('object');
                $this->txt = $remaining;
                return $temp;
            break;
        }

        //find closing
        preg_match($regex,$this->txt,$find,PREG_OFFSET_CAPTURE);
        $find = $find[0];

        $value = substr($this->txt,1,$find[1]-1);
        $this->txt = substr($this->txt,$find[1]+strlen($find[0])-1);
        if(strlen($this->txt)==1){
            $this->txt = '';
        }
        return $value;
        
    }
}
