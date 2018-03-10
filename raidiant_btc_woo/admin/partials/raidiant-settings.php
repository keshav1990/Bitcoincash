         <div id="btc-div">

<link rel="stylesheet" type="text/css" href="<?php echo radient_URL; ?>paymentbox/assets/wcss.css">





                  <input type="hidden" id="address_" value="[[address]]" onchange="make_address(this.value)">

                    <div class="bitcoin">

<div class="bitcoin_head">

<label>Total:<span> [[value]]</span> BCH</label>

<div class="bitcoin_logo wrapper">

<img src="<?php echo radient_URL; ?>paymentbox/assets/payment.png">

<div class="tooltip ws">Bitcoin Payment Gateway Powered By <b> Electrum</b></div>

</div>

</div>



<div class="coin_no">

 <div class=" coin_img wrapper">
    <a href="[[address]]?amount=[[value]]"><img id="qrsend" src="https://api.qrserver.com/v1/create-qr-code/?color=000000&amp;bgcolor=FFFFFF&amp;data=[[address]]%3Famount%3D[[value]]&amp;qzone=1&amp;margin=0&amp;size=180x180&amp;ecc=L" style="vertical-align:middle;border:1px solid #888;" /></a>



    <div class="tooltip code"><p>QR Code - BCH address and sum you can scan with a mobile phone camera. Click on camera icon, point the camera at the code, and you're done</p></div>





  </div>

  <div class="bitcoin_text">

  <ul>

  <li>1. Go to <a href="#">localbitcoins.com</a> and get Bitcoins (BCH) if you don't have it.</li>

  <li><div class="send_info wrapper">2. <a href="[[address]]?amount=[[value]]">Send </a> <div class="tooltip">Users send and receive Bitcoins Cash electronically using wallet software on a personal computer, mobile device.</div> </div> [[value]] Bitcoins Cash (in one payment) to the address below. If you send any other bitcoin amount, payment system will ignore it !</li>
  <li>3. <?php echo  __('You must make a payment within 8 hours, or your order will be cancelled', 'woocommerce' ) ; ?></li>
  <li>4. <?php echo  __('As soon as your payment is received in full you will receive email confirmation with order delivery details.', 'woocommerce' )  ; ?></li>
  </ul>

   <div class="send_exact">

  Send exactly <span>[[value]]</span> BCH (plus miner fee) to:</div>

  <div class="open_wallet">

  <div class="wrapper wallet_text">

  <a href="[[address]]?amount=[[value]]" id="bitaddress"><b>[[address]]</b></a>

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


            </div>
