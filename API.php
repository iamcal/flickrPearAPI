<?php
	#
	# PEAR::Flickr_API
	#
	# Author: Cal Henderson
	# Version: $Version$
	# CVS: $Id: API.php,v 1.1 2005/02/28 22:32:57 cal Exp $
	#


	require_once 'XML/Tree.php';
	require_once 'HTTP/Request.php';


	class Flickr_API {

		var $_cfg = array(
				'api_key'	=> '',
				'endpoint'	=> 'http://www.flickr.com/services/rest/',
				'conn_timeout'	=> 5,
				'io_timeout'	=> 5,
			);

		var $_err_code = 0;
		var $_err_msg = '';

		function Flickr_API($params = array()){

			foreach($params as $k => $v){
				$this->_cfg[$k] = $v;
			}
		}

		function callMethod($method, $params = array()){

			$this->_err_code = 0;
			$this->_err_msg = '';

			#
			# create the POST body
			#

			$p = $params;
			$p['method'] = $method;
			$p['api_key'] = $this->_cfg['api_key'];

			$p2 = array();
			foreach($p as $k => $v){
				$p2[] = urlencode($k).'='.urlencode($v);
			}

			$body = implode('&', $p2);


			#
			# create the http request
			#

			$req =& new HTTP_Request($this->_cfg['endpoint'], array('timeout' => $this->_cfg['conn_timeout']));

			$req->_readTimeout = array($this->_cfg['io_timeout'], 0);

			$req->setMethod(HTTP_REQUEST_METHOD_POST);
			$req->addRawPostData($body);

			$req->sendRequest();

			$this->_http_code = $req->getResponseCode();
			$this->_http_head = $req->getResponseHeader();
			$this->_http_body = $req->getResponseBody();

			if ($this->_http_code != 200){

				$this->_err_code = 0;

				if ($this->_http_code){
					$this->_err_msg = "Bad response from remote server: HTTP status code $this->_http_code";
				}else{
					$this->_err_msg = "Couldn't connect to remote server";
				}

				return 0;
			}


			#
			# create xml tree
			#

			$tree =& new XML_Tree();
			$tree->getTreeFromString($this->_http_body);


			#
			# check we got an <rsp> element at the root
			#

			if ($tree->root->name != 'rsp'){

				$this->_err_code = 0;
				$this->_err_msg = "Bad XML response";

				return 0;
			}


			#
			# stat="fail" ?
			#

			if ($tree->root->attributes['stat'] == 'fail'){

				$n = $tree->root->children[0]->attributes;

				$this->_err_code = $n['code'];
				$this->_err_msg = $n['msg'];

				return 0;
			}


			#
			# weird status
			#

			if ($tree->root->attributes['stat'] != 'ok'){

				$this->_err_code = 0;
				$this->_err_msg = "Unrecognised REST response status";

				return 0;
			}


			#
			# return the tree
			#

			return $tree->root;
		}


		function getErrorCode(){
			return $this->_err_code;
		}

		function getErrorMessage(){
			return $this->_err_msg;
		}
	}


?>