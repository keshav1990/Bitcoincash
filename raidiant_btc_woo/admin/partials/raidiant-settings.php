
<link rel="stylesheet" type="text/css" href="./assets/wcss.css">
    <script type="text/javascript">

    function myFunction() {
        //bitaddress
        bitcoinAddress = $("#bitaddress").text();
    var person = prompt("Copy Bit Coin Address", bitcoinAddress);
    }
    function make_address(bitcoin_){
       $('#bitaddress').text(bitcoin_);
       $('#bitaddress').attr('href','bitcoin:'+bitcoin_+'?amount=0.00001877&label=Payment');
    }
    </script>
            <div class="blockchain stage-loading" style="text-align:center">
                <img src="./assets/loading-large.gif">
            </div>

                  <input type="hidden" id="address_" value="[[address]]" onchange="make_address(this.value)">
                    <div class="bitcoin">
<div class="bitcoin_head">
<label>Total:<span> 0.00001877</span> BTC</label>
<div class="bitcoin_logo wrapper">
<img src="./assets/payment.png">
<div class="tooltip ws">Bitcoin Payment Gateway Powered By <b> Neo Web Solutions</b></div>
</div>
</div>

<div class="coin_no">
 <div class=" coin_img wrapper">
   <img id="qrsend" src="./assets/qr">

    <div class="tooltip code"><p>QR Code - BCH address and sum you can scan with a mobile phone camera. Click on camera icon, point the camera at the code, and you're done</p>	<span><img src="./assets/button.jpg"></span></div>


  </div>
  <div class="bitcoin_text">
  <ul>
  <li>1. Go to <a href="http://neowebsolution.com/bitcoin/#">localbitcoins.com</a> and get Bitcoins (BCH) if you don't have it.</li>
  <li><div class="send_info wrapper">2. <a href="#">Send</a><div class="tooltip">Users send and receive Bitcoins Cash electronically using wallet software on a personal computer, mobile device.</div> </div> 0.00001877 Bitcoins (in one payment) to the address below.
    If you send any other bitcoin amount, payment system will ignore it !</li>

  </ul>
   <div class="send_exact">
  Send exactly <span>[[value]]</span> BCH (plus miner fee) to:</div>
  <div class="open_wallet">
  <div class="wrapper wallet_text">
  <a href="[[address_safe]]" id="bitaddress"><b>[[address]]</b></a>
     <div class="tooltip">BCH Wallet Address is unique identifier which allows users to receive and send Bitcoins Cash.</div>
  </div>
  <div class="wallet_btn wrapper">


  </div>
  </div>



</div>
<div class="copy_adrs">
<div class="copy_btn wrapper">
<button onclick="myFunction()">Copy Address</button>
<div class="tooltip">
Copy Address to Bitcoin Cash Wallet manually</div>
</div>
<div class="payment_btn wrapper">
<button><span class="btn-spinner"></span> Waiting Payment From You</button>
<div class="tooltip">Please send the exact Bitcoin sum as shown - [[value]] BCH (in one payment)! If you send any other sum, payment system will ignore the transaction and you will need to send the correct sum again. If you have already sent Bitcoin Cash to the address above, please wait 1-2 min to receive them by payment system</div>
</div>
</div>

</div>




            </div>
            <div class="blockchain stage-paid">
                Payment Received <b>[[value]] BCH</b>. Thank You.
            </div>
            <div class="blockchain stage-error">
                <font color="red">[[error]]</font>
            </div>
