<?php
/*
- this program should be run with command line with no parameter. eg: 'php bowlinggame.php'
- All classes are put into one file for review
- Because of the time constraint, I didn't write very detailed documentation for each class and each function.
Here is the list of variables i used in the program
$numOfPinsDown - the result of last roll
$nthFrame   - represents the frame index. range (1 -10)
$nthRollInFrame - represents the roll index at a given frame
$nthRollInGame - represents the roll index at one game
$rollStatusArr - array to score the roll status: if it is strike, a spare 
$pinsDownPerRoll - array to score how many pins were knocked down per roll. keyed on roll index.
$frameScoresArr  - Array that scores scores for all frames keyed on the frame index
$totalScore     - total score of the game at any given time
*/

class BowlingGame
{
	private $decisionMakerObj;
	private $ioHandler;
	private $scoreBoardObj;
	
	function __construct()
	{
		$this->decisionMakerObj = new BowlingGameDecisionMaker();
		$this->ioHandler = fopen ("php://stdin","r");
		$this->scoreBoardWriterObj = new ScoreBoardWriter();
	}
	
	/*
		Start the game. At the end the one game, ask the user if he wants to continue. If user enters 'yes', start a new game
	*/
	public function Start()
	{
		$continue = 'yes';
		while(strtolower($continue) == 'yes')
		{
			echo "-----------------Game Start -----------------------\r\n";
			
			$nthRollInGame = 0;
			$rollStatusArr = array();
			$frameScoresArr = array();
			$totalScore    = '';
			$pinsDownPerRoll = array();
			$numOfPinsDownPerFrame = array();
			
			for($nthFrame = 1; $nthFrame <= 10; $nthFrame++)
			{
				$this->OneFrame($nthFrame, $nthRollInGame, $pinsDownPerRoll, $numOfPinsDownPerFrame, $rollStatusArr, $frameScoresArr, $totalScore);
			}
			
			echo "Do you want to start a new game(yes to continue):";
			
			$continue = trim(fgets($this->ioHandler));
		}
		echo "-----------------Good Bye -----------------------\r\n";
		exit();
	}
	
	/*
		This function handles one frame of the game
	*/
	public function OneFrame($nthFrame, &$nthRollInGame, &$pinsDownPerRoll, &$numOfPinsDownPerFrame, &$rollStatusArr, &$frameScoresArr, &$totalScore)
	{
		$nthRollInFrame = 0;
		$numOfPinsDown = 0;
		
		while($nthRollInFrame == 0 || $this->decisionMakerObj->nextRollAvailableInFrame($nthFrame, $nthRollInFrame, $numOfPinsDown, $numOfPinsDownPerFrame, $nthRollInGame, $rollStatusArr))
		{
			echo "Playing frame: $nthFrame. Please enter how many pins were knocked down with roll ".($nthRollInFrame + 1).":  ";
			 
			$numOfPinsDown = trim(fgets($this->ioHandler));
			 
			if(!is_numeric($numOfPinsDown)) 
			{
				echo "Error: Input should be an integer and less than 10\r\n";
				continue;
			}
			else if($numOfPinsDown > 10)
			{
				echo "Error: Input should be less than 10\r\n";
				continue;
			}
			
			if($nthFrame < 10 && ($nthRollInFrame + 1 == 2) && ($numOfPinsDown + $pinsDownPerRoll[$nthRollInGame] >10))
			{
				echo "Invalid Input. There is a total of 10 pins per frame\r\n";
				continue;
			}
			
			$nthRollInFrame++;
			$nthRollInGame++;
			$numOfPinsDownPerFrame[$nthFrame][$nthRollInFrame] = $numOfPinsDown;
			$pinsDownPerRoll[$nthRollInGame] = $numOfPinsDown;
			$this->decisionMakerObj->SetRollStatus($nthFrame, $nthRollInFrame, $numOfPinsDown, $numOfPinsDownPerFrame, $nthRollInGame, $rollStatusArr);
			$this->decisionMakerObj->GetScores($numOfPinsDown, $nthFrame, $nthRollInFrame, $nthRollInGame, $rollStatusArr, $pinsDownPerRoll, $frameScoresArr, $totalScore);
			 
			$this->scoreBoardWriterObj->PrintScore($frameScoresArr, $totalScore);
			
		}
		
	}
}



class BowlingGameDecisionMaker
{
	/*
	This function is to decide if a roll is a strike.
	*/
	private function isStrike($numOfPinsDown)
	{
		return ($numOfPinsDown == 10);
	}
	
	/*
	This function is to decide if a roll is a spare.
	*/
	private function isSpare($nthFrame, $numOfPinsDown, $nthRollInFrame, $numOfPinsDownPerFrame)
	{
		if($nthRollInFrame != 2)
			return false;
		
		return ($numOfPinsDown + $numOfPinsDownPerFrame[$nthFrame][1] == 10);
	}
	
	/*
	This function is to decide if another roll is available within the same frame.
	*/
	public function nextRollAvailableInFrame($nthFrame, $nthRollInFrame, $numOfPinsDown, $numOfPinsDownPerFrame, $nthRollInGame, $rollStatusArr)
	{
		switch($nthRollInFrame)
		{
			case 1: 
				if($nthFrame != 10 && $rollStatusArr[$nthRollInGame] == 'strike')
					return false;
				else 
					return true;
				 
				break;
			case 2:
				if($nthFrame == 10 && ($rollStatusArr[$nthRollInGame] == 'spare' || $rollStatusArr[$nthRollInGame] == 'strike' ))
					return true;
				else	
					return false;
			break;
			case 3:
				return false;
			break;
			default:
				return true;
			break;
		}
	
	}
	
	/*
	This function is to set an status array which will tell the status of a roll: it is a strike or a spare or neither.
	*/
	public function SetRollStatus($nthFrame, $nthRollInFrame, $numOfPinsDown, $numOfPinsDownPerFrame, $nthRollInGame, &$rollStatusArr)
	{
		if($this->isStrike($numOfPinsDown))
			$rollStatusArr[$nthRollInGame] 	= 'strike';
		else if($this->isSpare($nthFrame, $numOfPinsDown, $nthRollInFrame, $numOfPinsDownPerFrame))
			$rollStatusArr[$nthRollInGame] 	= 'spare';
	}
	
	 
	
	public function GetScores($numOfPinsDown,  $nthFrame, $nthRollInFrame, $nthRollInGame, $rollStatusArr, $pinsDownPerRoll, &$frameScoresArr, &$totalScore)
	{
		
		if($rollStatusArr[$nthRollInGame] == 'strike')
		{
			$frameScoresArr[$nthFrame]['pinsDown'][$nthRollInGame] = 'X';
			if($nthRollInFrame == 3 && $nthFrame == 10)
			{
				$frameScoresArr[$nthFrame]['frameTotal'] =  30;
				$totalScore += 30;
			}
		}
		
		else if($rollStatusArr[$nthRollInGame] == 'spare' )
		{
			$frameScoresArr[$nthFrame]['pinsDown'][$nthRollInFrame] = '/';
			 
			
		}
		else
		{
			$frameScoresArr[$nthFrame]['pinsDown'][$nthRollInFrame] = $numOfPinsDown;
			//only show score when the score for a frame is known
			if( $nthRollInFrame == 2)
			{
				$frameScoresArr[$nthFrame]['frameTotal'] = array_sum($frameScoresArr[$nthFrame]['pinsDown']);
				$totalScore += $frameScoresArr[$nthFrame]['frameTotal'];
			}
				
		}
		 
		if($rollStatusArr[$nthRollInGame-2] == 'strike' && $rollStatusArr[$nthRollInGame-1] == 'strike')
		{
			if($nthFrame != 10 || ($nthFrame == 10 && $nthRollInFrame == 1 ))
			{
				$frameScoresArr[$nthFrame-2]['frameTotal'] = $frameScoresArr[$nthFrame-2]['frameTotal'] + 20 + $numOfPinsDown;
				$totalScore += $frameScoresArr[$nthFrame-2]['frameTotal'];
			}
			else if($nthRollInFrame != 3)
			{
				$frameScoresArr[$nthFrame-1]['frameTotal'] = $frameScoresArr[$nthFrame-1]['frameTotal'] + 20 + $numOfPinsDown;
				$totalScore += $frameScoresArr[$nthFrame-1]['frameTotal'];
			}
			
		}
		
		if($rollStatusArr[$nthRollInGame-2] == 'strike' && $rollStatusArr[$nthRollInGame-1] != 'strike')
		{
			$frameScoresArr[$nthFrame-1]['frameTotal'] = $frameScoresArr[$nthFrame-1]['frameTotal'] + 10 + $numOfPinsDown + $pinsDownPerRoll[$nthRollInGame-1];
			$totalScore += $frameScoresArr[$nthFrame-1]['frameTotal'];
		}
		
		if($rollStatusArr[$nthRollInGame-1] == 'spare')
		{
			$frameScoresArr[$nthFrame-1]['frameTotal'] = $frameScoresArr[$nthFrame-1]['frameTotal'] + 10 + $numOfPinsDown;
			$totalScore += $frameScoresArr[$nthFrame-1]['frameTotal'];
		}
		
	}
	
}


/*
Printing out the ScoreBoard
*/
class ScoreBoardWriter
{
	public function PrintScore($frameScoresArr, $totalScore)
	{
		if(is_array($frameScoresArr) && count($frameScoresArr))
		{
			echo "\r\n************************************************************************\r\n\r\n";
			echo "\t\t\t\t SCOREBOARD \t\t\t\r\n";
			foreach($frameScoresArr as $nthFrame => $frameScoreBoard)
			{
				//print_r($frameScoreBoard);
				echo "Frame $nthFrame : ";
				echo implode('|', $frameScoreBoard['pinsDown']);
				 
				echo " Total: ".$frameScoreBoard['frameTotal']."\r\n";
				
			}
			echo "Game Total: $totalScore\r\n";
			echo "\r\n************************************************************************\r\n\r\n\r\n";
		}
	}
}



$game = new BowlingGame();
$game->Start();

?>
