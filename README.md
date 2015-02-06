# redis_exception_handling
php predis modification to handle uncaught exceptions


when initializing model() class  wrap with try/catch 

	try {
		
		$model_call = new Model();
		} 
            catch (Exception $e) {
		      $data =  $e->getMessage();
              
              }