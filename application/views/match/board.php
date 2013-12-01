
<!DOCTYPE html>

<html>
	
<head>

	<script src="http://code.jquery.com/jquery-latest.js"></script>
	<script src="<?= base_url() ?>/js/jquery.timers.js"></script>
	<script type="text/javascript" src="<?= base_url() ?>/js/scripts/board.php"></script>
	<link rel='stylesheet' type='text/css' href="<?= base_url(); ?>css/gameboard.css"></link>
	<link rel='stylesheet' type='text/css' href="<?= base_url(); ?>css/style.css"></link>
	<script>
	var otherUser = "<?= $otherUser->login ?>";
	var user = "<?= $user->login ?>";
	var status = "<?= $status ?>";
	$(document).ready(function () {

	    $('form').submit(function () {
	        var arguments = $(this).serialize();
	        var url = "<?= base_url() ?>board/postMsg";
	        $.post(url, arguments, function (data, textStatus, jqXHR) {
	            var conversation = $('[name=conversation]').val();
	            var msg = $('[name=msg]').val();
	            $('[name=conversation]').val(conversation + "\n" + user + ": " + msg);
	        });
	        return false;
	    });

	    $('body').everyTime(2000, function () {
	        if (status == 'waiting') {
	            $.getJSON('<?= base_url() ?>arcade/checkInvitation', function (data, text, jqZHR) {
	                if (data && data.status == 'rejected') {
	                    alert("Sorry, your invitation to play was declined!");
	                    window.location.href = '<?= base_url() ?>arcade/index';
	                }
	                if (data && data.status == 'accepted') {
	                    status = 'playing';
	                    $('#status').html('Playing ' + otherUser);
	                    window.location.href = '<?= base_url() ?>board/index';
	                }
	
	            });
	        }
	        var url = "<?= base_url() ?>board/getMsg";
	        $.getJSON(url, function (data, text, jqXHR) {
	            if (data && data.status == 'success') {
	                var conversation = $('[name=conversation]').val();
	                var msg = data.message;
	                if (msg != null && msg.length > 0 ) {
	                    $('[name=conversation]').val(conversation + "\n" + otherUser + ": " + msg);
	                }
	            }
	        });

		});
	});
</script>

</head>
<body>
	<h1>Game Area</h1>
	<div class="infoMessage">
		<div class="greetingMessage">
			Hello
			<?= $user->fullName() ?>
			<?= anchor('account/logout','(Logout)') ?>
		</div>
		<div id='status'>
			<?php 
				if ($status == "playing")
					echo "Playing " . $otherUser->login;
				else
					echo "Waiting on " . $otherUser->login;
			?>
		</div>
	</div>
	<br />
	<table class="gameBoard">
		<?php 
			for ($row = 0; $row < 6; $row++) {
				echo "<tr class='row$row'>";
				for ($col = 0; $col < 7; $col++) {
					echo "<td><div id='row$row-col$col' class='circleBase boardSlot emptySlot'></div></td>";
				}
				echo "</tr>";
			}

			if (isset($userPlayerID)) {
				echo $userPlayerID;
			}
		?>
	</table>
	<div class="chatSection">
		<?php 
			echo form_textarea(array('name' => 'conversation', 'disabled'));
			echo form_open();
			echo form_input('msg');
			echo form_submit('Send','Send');
			echo form_close();
		?>
	</div>
	<script>
	var userID;
	var currTurnID;
	var opponentID;
	var opponentStrID;
	var opponentMadeMoveURL;
	var makeMoveURL;

	Array.prototype.max = function() {
		 return Math.max.apply(null, this);
	};

	$(document).ready(function() {
		
		currTurnID = <?php echo $currentTurn; ?>;
		userID = <?php echo $userPlayerID; ?>;
		opponentID = 3 - userID;
		opponentStrID = opponentID.toString();
		opponentMadeMoveURL = "<?= base_url() ?>board/opponentMadeMove";
		makeMoveURL = "<?= base_url() ?>board/makeMove";

		// Inserts a token into a slot, if it's the user's turn.
		$('body').delegate('.emptySlot','click', makeMove);

		
		// If it's the opponent's turn, this waits for the opponent to make a move.
		
		$('body').everyTime(200, waitForOpponent);

		
	});

	function waitForOpponent() {
		if (userID != currTurnID) {
	        $.getJSON(opponentMadeMoveURL, function (data, text, jqXHR) {
	            if (data && data.status == 'success' && parseInt(data.col_num) != -1 && parseInt(data.row_num) != -1) {
					var idStr = "#row" + data.row_num.toString() + "-col" +  data.col_num.toString();
					$(idStr).addClass('player' + opponentStrID).removeClass('emptySlot');
					currTurnID = 3 - currTurnID;
	            }
	        });
	        
		}
	}
	
	
	function makeMove() {
		
		if (userID == currTurnID) {
			var thisColNum = extractColNum($(this));
			var lowestSlot = getLowestRowInColumn(thisColNum);
			lowestSlot.addClass('player' + userID).removeClass('emptySlot');

			var coordinates = new Array(thisColNum, extractRowNum(lowestSlot));
			var argArray = {"currentPlayerTurn": currTurnID, "pieceAdded": coordinates};
			var arguments = $.param(argArray);

	        $.post(makeMoveURL, arguments, function (data, textStatus, jqXHR) {
				
		    });
			
	        currTurnID = 3 - currTurnID;
	        
		}
	}
	
	function getLowestRowInColumn(colNum) {
		
		slots = new Array();
		$(".boardSlot.emptySlot").each(function() { 

			var thisColNum = extractColNum($(this));
			if (colNum == thisColNum) {
				slots.push(extractRowNum($(this)));
			}
		});
	
		var lowestRow = slots.max();
		return getByRowColIndex(lowestRow, colNum);
		
	}
	
	function getByRowColIndex(row, col) {
	
		retVal = null;
		$(".boardSlot.emptySlot").each(function() {
			if ((extractRowNum($(this)) == row) && (extractColNum($(this)) == col)) {
				retVal = $(this);
				return false;
			} 
		});
		return retVal;
	
	}
	
	function extractRowNum(slot) {
		var regex = /row(\d)-col\d/;
		var returnSlot = slot.attr('id').replace(regex, "$1");
		return parseInt(returnSlot);
	}
	
	function extractColNum(slot) {
		var regex = /row\d-col(\d)/;
		var returnSlot = slot.attr('id').replace(regex, "$1");
		return parseInt(returnSlot);
	}
</script>
 
 
</body>

</html>


<!--  <canvas id="myCanvas" width="500" height="500" style="border:5px solid #c3c3c3;"> </canvas> -->
<!-- <div class='circleBase type1'></div> -->
<!-- <div class='circleBase type2'></div> -->

<!-- <div class='circleBase type2'></div> -->

<!-- <div class='circleBase type3'></div> -->
<!-- 
$(document).ready(function(){
	
  /*	$("p").click(function(){
	var a=  document.getElementById("0-0");
	var scrolltimes = 0;
	$(document).ready(function() {
	$('#clay').scroll(function() {
	$('#scrollamount p').html({'<p>Scrolled: '+ .scrolltimes++ + '</p>'});
	});*/
});


-->
