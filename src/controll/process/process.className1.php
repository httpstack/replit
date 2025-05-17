<?php

	require("../../domain/connection.php");
	require("../../domain/classname1.php");

	class ClassName1Process {
		var $cd;

		function doGet($arr){
			$cd = new ClassName1DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}


		function doPost($arr){
			$cd = new ClassName1DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}


		function doPut($arr){
			$cd = new ClassName1DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}


		function doDelete($arr){
			$cd = new ClassName1DAO();
			$result = "use to result to DAO";
			http_response_code(200);
			echo json_encode($result);
		}
	}