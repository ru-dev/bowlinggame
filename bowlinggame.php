<?php
/*
- this program should be run with command line with no parameter. eg: 'php bowlinggame.php'
- All classes are put into one file for review
- Because of the time constraint, I didn't write very detailed documentation for each class and each function.
	Here is the list of variables I used in the program
	$numOfPinsDown - the result of last roll
	$currentFrame   - represents the current frame index. range (1 -10)
	$nthRollInFrame - represents the roll index in the current frame
	$nthRollInGame - represents the roll index at current game
	$rollStatusArr - array to score the roll status: if it is strike or a spare 
	$pinsDownByRoll - array to score how many pins were knocked down per roll. keyed on roll index.
	$frameScoresArr  - Array that scores scores for all frames keyed on the frame index
	$totalScore     - total score of the game at any given time
	
	Here is the sample output:
	************************************************************************

							 SCOREBOARD
	Frame 1:   6|-          Total: 12
	Frame 2:   2|4          Total: 6
	Frame 3:   X            Total: 20
	Frame 4:   5|-          Total: 20
	Frame 5:   X            Total: 30
	Frame 6(@@):   X                Total: 30
	Frame 7(@@):   X                Total: 29
	Frame 8(@@):   X                Total: 20
	Frame 9:   9|-          Total: 20
	Frame 10:   X|7|1               Total: 18
	Game Total: 205

	************************************************************************
*/

class BowlingGame
{
	private $decisionRuleObj;
	private $ioHandler;
	private $scoreBoardObj;
	private $nthRollInGame = 0;
	private	$rollStatusArr = array();
	private	$frameScoresArr = array();
	private $pinsDownByRoll = array();
	private $currentFrame = 0;
	private $frameStatusArr = array();
	
	function __construct()
	{
		$this->decisionRuleObj = new BowlingGameRules();
		$this->ioHandler = fopen ("php://stdin","r");
		$this->scoreBoardWriterObj = new ScoreBoardWriter();
	}
	
	/*
		Run the game. At the end the one game, ask the user if he wants to continue. If user enters 'yes', start a new game
	*/
	public function Run()
	{
		$continue = 'yes';
		while(strtolower($continue) == 'yes')
		{
			$this->ReSetVars();
			echo "-----------------Game Start -----------------------\r\n";
			
			for($this->currentFrame = 1; $this->currentFrame <= 10; $this->currentFrame++)
			{
				$this->RunOneFrame();
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
	public function RunOneFrame()
	{
		$nthRollInFrame = 0;
		$numOfPinsDown = 0;
		
		while($nthRollInFrame == 0 || $this->decisionRuleObj->nextRollAvailableInFrame($this->currentFrame, $nthRollInFrame, $numOfPinsDown, $this->nthRollInGame, $this->rollStatusArr))
		{
			echo "Playing frame: ".$this->currentFrame.". Please enter how many pins were knocked down with roll ".($nthRollInFrame + 1).":  ";
			 
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
			
			if(($this->currentFrame < 10 && $nthRollInFrame == 1 && ($numOfPinsDown + $this->pinsDownByRoll[$this->nthRollInGame] >10)) ||
			   ($this->currentFrame == 10 && $this->rollStatusArr[$this->nthRollInGame] != 'STRIKE' && $this->rollStatusArr[$this->nthRollInGame] != 'SPARE' && ($numOfPinsDown + $this->pinsDownByRoll[$this->nthRollInGame] >10)))
			{
				echo "Invalid Input. There are not enough pins left.\r\n";
				continue;
			}
			
			$nthRollInFrame++;
			$this->nthRollInGame++;
			$this->pinsDownByRoll[$this->nthRollInGame] = $numOfPinsDown;
			list($this->rollStatusArr, $this->frameStatusArr) = $this->decisionRuleObj->SetStatus($this->currentFrame, $nthRollInFrame, $numOfPinsDown, $this->nthRollInGame, $this->pinsDownByRoll, $this->rollStatusArr, $this->frameStatusArr);
			$this->frameScoresArr = $this->decisionRuleObj->SetScores($numOfPinsDown, $this->currentFrame, $nthRollInFrame, $this->nthRollInGame, $this->rollStatusArr, $this->pinsDownByRoll, $this->frameScoresArr);
			$this->scoreBoardWriterObj->PrintScore($this->frameScoresArr, $this->rollStatusArr, $this->frameStatusArr);
			
		}
		
	}
	
	/*
		Reset the variables before starting the game
	*/
	private function ReSetVars()
	{
		$this->nthRollInGame = 0;
	 	$this->rollStatusArr = array();
	 	$this->frameScoresArr = array();
		$this->pinsDownByRoll = array();
		$this->frameStatusArr = array();
	}
}



class BowlingGameRules
{
	/*
	This function is used to decide if a roll is a strike.
	*/
	private function isStrike($numOfPinsDown, $currentFrame,  $nthRollInFrame, $pinsDownByRoll, $nthRollInGame)
	{
		return (($nthRollInFrame == 1 || ($currentFrame == 10 && $nthRollInFrame> 1 && $pinsDownByRoll[$nthRollInGame-1] != 0 )) && $numOfPinsDown == 10);
	}
	
	/*
	This function is used to decide if a roll is a spare.
	*/
	private function isSpare($numOfPinsDown, $currentFrame, $nthRollInFrame, $nthRollInGame, $pinsDownByRoll)
	{
		if($nthRollInFrame != 2 && ($currentFrame == 10 && $nthRollInFrame != 3))
			return false;
		
		return ($numOfPinsDown && ($numOfPinsDown + $pinsDownByRoll[$nthRollInGame-1] == 10) && !($nthRollInFrame == 3 && $pinsDownByRoll[$nthRollInGame-2] + $pinsDownByRoll[$nthRollInGame-1] == 10));
	}
	
	private function isTurkey($currentFrame, $numOfPinsDown, $nthRollInFrame, $nthRollInGame, $pinsDownByRoll)
	{
		if($currentFrame == 1 || $nthRollInFrame == 2)
			return false;
		
		return ($this->isStrike($numOfPinsDown, $currentFrame, $nthRollInFrame, $pinsDownByRoll, $nthRollInGame) && $this->isStrike($pinsDownByRoll[$nthRollInGame-1],$currentFrame, $nthRollInFrame, $pinsDownByRoll, $nthRollInGame));
	}
	
	/*
	This function is used to decide if another roll is available within the same frame.
	*/
	public function nextRollAvailableInFrame($currentFrame, $nthRollInFrame, $numOfPinsDown, $nthRollInGame, $rollStatusArr)
	{
		switch($nthRollInFrame)
		{
			case 1: 
				if($currentFrame != 10 && $rollStatusArr[$nthRollInGame] == 'STRIKE')
					return false;
				else 
					return true;
				 
				break;
			case 2:
				if($currentFrame == 10 && ($rollStatusArr[$nthRollInGame] == 'SPARE' || $rollStatusArr[$nthRollInGame] == 'STRIKE' ||$rollStatusArr[$nthRollInGame-1] == 'STRIKE'   ))
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
	This function populates an array which stores the status of a roll: it is a strike or a spare or neither.
	*/
	public function SetStatus($currentFrame, $nthRollInFrame, $numOfPinsDown, $nthRollInGame, $pinsDownByRoll, $rollStatusArrOriginal, $frameStatusArrOriginal)
	{
		$rollStatusArr = $rollStatusArrOriginal;
		$frameStatusArr = $frameStatusArrOriginal;
		
		if($this->isStrike($numOfPinsDown, $currentFrame, $nthRollInFrame, $pinsDownByRoll, $nthRollInGame))
			$rollStatusArr[$nthRollInGame] 	= 'STRIKE';
		else if($this->isSpare($numOfPinsDown, $currentFrame, $nthRollInFrame, $nthRollInGame, $pinsDownByRoll))
			$rollStatusArr[$nthRollInGame] 	= 'SPARE';
		if($this->isTurkey($currentFrame, $numOfPinsDown, $nthRollInFrame, $nthRollInGame, $pinsDownByRoll))
			$frameStatusArr[$currentFrame] 	= 'TURKEY';
		return array($rollStatusArr, $frameStatusArr);
	}
	
	 
	/*
	This function sets score for a frame when the score can be calculated
	*/
	public function SetScores($numOfPinsDown, $currentFrame, $nthRollInFrame, $nthRollInGame, $rollStatusArr, $pinsDownByRoll, $frameScoresArrOriginal)
	{
		$frameScoresArr = $frameScoresArrOriginal;
		
		$frameScoresArr[$currentFrame]['pinsDown'][$nthRollInGame] = $numOfPinsDown;
		//only show score when the score for a frame is known
		if( $rollStatusArr[$nthRollInGame] != 'STRIKE' && $rollStatusArr[$nthRollInGame] != 'SPARE' &&($nthRollInFrame == 2 && !($currentFrame == 10 && $rollStatusArr[$nthRollInGame-1] == 'STRIKE' )) ||
		($currentFrame == 10 && $nthRollInFrame == 3))
		{
			$frameScoresArr[$currentFrame]['frameTotal'] = array_sum($frameScoresArr[$currentFrame]['pinsDown']);
			 
		}
		
		if($rollStatusArr[$nthRollInGame] == 'STRIKE' && $nthRollInFrame == 3) //last roll in game
		{
			$frameScoresArr[$currentFrame]['frameTotal'] =  10 + $pinsDownByRoll[$nthRollInGame-1] + $pinsDownByRoll[$nthRollInGame-2] ;
		}
			 
		
		//Adding score to the previous frame(s) if applicable
		if($rollStatusArr[$nthRollInGame-2] == 'STRIKE' && $rollStatusArr[$nthRollInGame-1] == 'STRIKE')
		{
			if($currentFrame != 10 || ($currentFrame == 10 && $nthRollInFrame == 1 ))
			{
				$frameScoresArr[$currentFrame-2]['frameTotal'] = $frameScoresArr[$currentFrame-2]['frameTotal'] + 20 + $numOfPinsDown;
				 
			}
			else if($nthRollInFrame != 3)
			{
				$frameScoresArr[$currentFrame-1]['frameTotal'] = $frameScoresArr[$currentFrame-1]['frameTotal'] + 20 + $numOfPinsDown;
				 
			}
			
		}
		
		if($rollStatusArr[$nthRollInGame-2] == 'STRIKE' && $rollStatusArr[$nthRollInGame-1] != 'STRIKE')
		{
			if(!($currentFrame == 10&& $nthRollInFrame == 3))
			{
				$frameScoresArr[$currentFrame-1]['frameTotal'] = $frameScoresArr[$currentFrame-1]['frameTotal'] + 10 + $numOfPinsDown + $pinsDownByRoll[$nthRollInGame-1];
				 
			}
			else
			{
				$frameScoresArr[$currentFrame]['frameTotal'] =  10 + $pinsDownByRoll[$nthRollInGame-1] + $numOfPinsDown;
			 
			}
			
		}
		
		if($rollStatusArr[$nthRollInGame-1] == 'SPARE')
		{
			if($nthRollInFrame != 3) //not the 3rd roll in 10th frame
			{
				$frameScoresArr[$currentFrame-1]['frameTotal'] = $frameScoresArr[$currentFrame-1]['frameTotal'] + 10 + $numOfPinsDown;
			}
		}
		return $frameScoresArr;
	}
	
}


/*
Printing out the ScoreBoard
*/
class ScoreBoardWriter
{
	/*
		printing the score. 
	*/
	public function PrintScore($frameScoresArr, $rollStatusArr, $frameStatusArr)
	{
		//print_r($frameScoresArr);
		if(is_array($frameScoresArr) && $frameCount = count($frameScoresArr))
		{
			echo "\r\n************************************************************************\r\n\r\n";
			echo "\t\t\t\t SCOREBOARD \t\t\t\r\n";
			$rollInGameCount = 1;
			$totalScore = 0;
			foreach($frameScoresArr as $currentFrame => $frameScoreBoard)
			{
				//print_r($frameScoreBoard);
				$turkeyMark =($frameStatusArr[$currentFrame] == 'TURKEY')? '(@@)' : '';
				echo "Frame $currentFrame$turkeyMark:   ";
				
				$totalRollCount = count($frameScoreBoard['pinsDown']);
				$rollInFrameCount = 1;
				foreach($frameScoreBoard['pinsDown'] as $oneScore)
				{
					if($rollStatusArr[$rollInGameCount] == 'STRIKE')
						echo "X";
					else if ($rollStatusArr[$rollInGameCount] == 'SPARE')
						echo "-";
					else
						echo $oneScore;
					
					if($rollInFrameCount < $totalRollCount)
						echo "|";
					
					$rollInFrameCount++;
					$rollInGameCount++;
				}
				
				echo " \t\tTotal: ".$frameScoreBoard['frameTotal']."\r\n";
				
				$totalScore += $frameScoreBoard['frameTotal'];
			}
			$totalScoreStr = ($totalScore)? $totalScore : '';
			echo "Game Total: $totalScoreStr\r\n";
			echo "\r\n************************************************************************\r\n\r\n\r\n";
		}
	}
}

/*==========================================================================================================
Start the game
*/

$game = new BowlingGame();
$game->Run();

?>
