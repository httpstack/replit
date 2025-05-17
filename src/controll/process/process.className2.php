<?php

	require("../../domain/connection.php");
	require("../../domain/classname2.php");

	class ClassName2Process {
		var $cd;

		function doGet($arr){
			$cd = new ClassName2DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}


		function doPost($arr){
			$cd = new ClassName2DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}


		function doPut($arr){
			$cd = new ClassName2DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}


		function doDelete($arr){
			$cd = new ClassName2DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}
	}