<?php
/*
- this program should be run with command line with no parameter. eg: 'php bowlinggame.php'
- All classes are put into one file for review
- 
*/
class BowlingGameScoreCounter
{
	private $numOfPinsDownAllFrames;
	
	public function SetNumOfPinsDownAllFrames($numOfPinsDownAllFrames)
	{
		$this->numOfPinsDownAllFrames = $numOfPinsDownAllFrames;
	}
	
	public function GetTotalScore()
	{
		foreach($this->numOfPinsDownAllFrames as $resultPerFrameArray)
		{
			$total += array_sum($resultPerFrameArray);
		}
		
		return $total;
	}
	
	public function GetScoreForFrame($nthFrame)
	{
		return array_sum($this->numOfPinsDownAllFrames[$nthFrame]);
	}
}




class BowlingGameDecisionMaker
{
	private function isStrike($numOfPinsDown)
	{
		return ($numOfPinsDown == 10);
	}
	
	private function isSpare($nthFrame, $numOfPinsDown, $nthBallInFrame, $numOfPinsDownAllFrames)
	{
		if($nthBallInFrame != 2)
			return false;
		return ($numOfPinsDown + $numOfPinsDownAllFrames[$nthFrame][1] == 10);
	}
	
	 
	public function nextBallAvailableInFrame($nthFrame, $nthBallInFrame, $numOfPinsDown, $numOfPinsDownAllFrames)
	{
		 
		switch($nthBallInFrame)
		{
			case 2:
				if(($nthFrame != 10 && $this->isStrike($numOfPinsDown)) || ($nthFrame == 10 && ($this->isStrike($numOfPinsDown) || $this->isSpare($nthFrame, $numOfPinsDown, $nthBallInFrame, $numOfPinsDownAllFrames))))
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
	
}

class BowlingGame
{
	static $numOfPinsDownAllFrames = array();
	private $decisionMakerObj;
	private $ioHandler;
	private $scoreBoardObj;
	
	
	function __construct()
	{
		$this->decisionMakerObj = new BowlingGameDecisionMaker();
		$this->ioHandler = fopen ("php://stdin","r");
		$this->scoreBoardObj = new ScoreBoard();
		$this->bowlingGameScoreCounterObj = new BowlingGameScoreCounter();
	}
	
	public function Start()
	{
		$continue = 'yes';
		
		while(strtolower($continue) == 'yes')
		{
			echo "Game Start\r\n";
		
			for($i = 1; $i <= 10; $i++)
			{
				$this->OneFrame($i);
			}
			
			echo "Do you want to start a new game(yes to continue):";
			
			$continue = trim(fgets($this->ioHandler));
		}
	}
	
	public function OneFrame($nthFrame)
	{
		$nthBallInFrame = 0;
		$numOfPinsDown = 0;
		while($nthBallInFrame == 0 || $this->decisionMakerObj->nextBallAvailableInFrame($nthFrame, $nthBallInFrame, $numOfPinsDown, $this->numOfPinsDownAllFrames))
		{
			echo "Frame $nthFrame: How many pins were knocked down with ball number ".($nthBallInFrame + 1)."? ";
			 
			$numOfPinsDown = trim(fgets($this->ioHandler));
			
			$this->numOfPinsDownAllFrames[$nthFrame][$nthBallInFrame] = $numOfPinsDown;
			
			$this->scoreBoardObj->SetNumOfPinsDownAllFrames($this->numOfPinsDownAllFrames);
			
			$nthBallInFrame++;
			$this->scoreBoardObj->PrintScore();
		}
		
	}
}

class ScoreBoard
{
	private $numOfPinsDownAllFrames;
	private $bowlingGameScoreCounterObj;
	
	function __construct()
	{
		$this->bowlingGameScoreCounterObj = new BowlingGameScoreCounter();
	}
	public function SetNumOfPinsDownAllFrames($numOfPinsDownAllFrames)
	{
		$this->numOfPinsDownAllFrames = $numOfPinsDownAllFrames;
		$this->bowlingGameScoreCounterObj->SetNumOfPinsDownAllFrames($numOfPinsDownAllFrames);
	}
	
	
	public function PrintScore()
	{
		if(is_array($this->numOfPinsDownAllFrames) && $totalFrame = count($this->numOfPinsDownAllFrames))
		{
			echo "\r\n************************************************************************\r\n";
			foreach($this->numOfPinsDownAllFrames as $nthFrame => $frameScoreBoard)
			{
				echo "Frame $nthFrame : ";
				echo implode('/', $frameScoreBoard)." Frame Total: ".$this->bowlingGameScoreCounterObj->GetScoreForFrame($nthFrame)."\r\n";
				
			}
			if($nthFrame == $totalFrame)
				echo "Game Total: ".$this->bowlingGameScoreCounterObj->GetTotalScore()."\r\n";
			echo "************************************************************************\r\n\r\n\r\n";
		}
	}
	
}

$game = new BowlingGame();
$game->Start();
?>
