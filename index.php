<?php
include('DRIVE_config.php');

//#1 - Accept POST references
$inputJSON = file_get_contents('php://input');
$DRIVE_paramter  = json_decode($inputJSON);
$DRIVE_token = getallheaders()["Authorization"];

$isValidNif = false;
//#2 - Check if exists nif
if(isset($DRIVE_paramter->customer->taxNumber)){
    //#3 - Is Valid NIF
    $isValidNif = UTILS_validateNIF($DRIVE_paramter->customer->taxNumber);

    //#4 - if NIF is IVALID
    if($isValidNif == false){
        $DRIVE_customer = $DRIVE_paramter->customer;
        
        //#4.1 - Remove customer property
        unset($DRIVE_paramter->customer);

        //#4.2 - Set customer number
        $DRIVE_paramter->document->customerNumber = DRIVE_genericConsumerNo;

        if(isset($DRIVE_paramter->document->customerName)){
            //#4.3 - Remove Customer name to dont try to reach it
            unset($DRIVE_paramter->document->customerName);
            
            //#4.4 - Set abreviated name in Document 
            $DRIVE_paramter->document->customerAbbreviatedName = $DRIVE_customer->name;
        }        
    }
    

    //#5 - Redirect the request to API
    $API_result = API_genericCall('/createDocument', $DRIVE_paramter);
    print_r($API_result);
    
}




print_r($DRIVE_paramter->customer->taxNumber);





//#A - Validate NIF
function UTILS_validateNIF($nif){
    
    //Limpamos eventuais espaços a mais
	$nif=trim($nif);
	//Verificamos se é numérico e tem comprimento 9
	if (!is_numeric($nif) || strlen($nif)!=9) {
		return false;
	} else {
		$nifSplit=str_split($nif);
		//O primeiro digíto tem de ser 1, 2, 5, 6, 8 ou 9
		//Ou não, se optarmos por ignorar esta "regra"
		if (
			in_array($nifSplit[0], array(1, 2, 5, 6, 8, 9))
			||
			$ignoreFirst
		) {
			//Calculamos o dígito de controlo
			$checkDigit=0;
			for($i=0; $i<8; $i++) {
				$checkDigit+=$nifSplit[$i]*(10-$i-1);
			}
			$checkDigit=11-($checkDigit % 11);
			//Se der 10 então o dígito de controlo tem de ser 0
			if($checkDigit>=10) $checkDigit=0;
			//Comparamos com o último dígito
			if ($checkDigit==$nifSplit[8]) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

}

//Make a generic Call to API
function API_genericCall($endpoint, $parameter){
    global $DRIVE_token;

    $url = DRIVE_api . $endpoint;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array("Content-type: application/json", "Authorization: " . $DRIVE_token));		
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameter));
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    $response = curl_exec($ch);

    return json_decode($response);

}


?>