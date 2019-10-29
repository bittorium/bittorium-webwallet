let scanner = new Instascan.Scanner({continuous: false, video: document.getElementById('preview'), mirror: false, scanPeriod: 5});
let _cameras = null;
Instascan.Camera.getCameras().then(function (cameras) {
  if (cameras.length > 0) {
    _cameras = cameras;
    let camerasElement = document.getElementById('cameras');
    camerasElement.innerHTML = "";
    cameras.forEach(function (item, index) {
      camerasElement.innerHTML += "<a onclick='javascript:startScan(" + index + ");' href='javascript:return false;'>" + "Camera " + index + "</a><br>";
    });
    camerasElement.innerHTML += "<a onclick='javascript:stopScan();' id='cancel' style='display: none;' href='javascript:return false;'>Cancel</a>";
    if (cameras.length === 1) {
      startScan(0);
    }
  } else {
    console.error('No cameras found.');
  }
}).catch(function (e) {
  console.error(e);
  let scanElement = document.getElementById('scan');
  scanElement.style.display = 'none';
  let videoElement = document.getElementById('preview');
  videoElement.style.display = 'none';
});

function startScan(index) {
  let videoElement = document.getElementById('preview');
  videoElement.style.display = 'block';
  let cancelElement = document.getElementById('cancel');
  cancelElement.style.display = 'inline';
  scanner.start(_cameras[index]);
}

function stopScan() {
  let videoElement = document.getElementById('preview');
  videoElement.style.display = 'none';
  let cancelElement = document.getElementById('cancel');
  cancelElement.style.display = 'none';
  scanner.stop();
}

function scanQR(el) {
  let addressElement = document.getElementsByName(el)[0];
  let paymentIDElement = document.getElementsByName('paymentID')[0];
  let amountElement = document.getElementsByName('amount')[0];
  let result = scanner.scan();
  if (result.content !== null) {
    let address = "";
    let paymentID = "";
    let amount = 0;
    let items = "";
    if (result.content.substr(0, 2) == "bT") {
      items = result.content.split(";");
      address = items[0];
      paymentID = items[1];
      amount = items[2];
    } else if (result.content.substr(0, 10) == "bittorium:") {
      items = result.content.split(":")[1].split("?");
      address = items[0];
      for (i = 1; i < items.length; i++) {
        let item = items[i].split("=");
        if (item[0] == 'amount') {
          amount = item[1] * 100;
        }
        if (item[0] == 'payment_id') {
          paymentID = item[1];
        }
      }
    }
    addressElement.setAttribute("value", address);
    if (paymentIDElement !== null) {
      paymentIDElement.setAttribute("value", paymentID);
    }
    if (amount > 0 && amountElement !== null) {
      amountElement.setAttribute("value", amount / 100);
    }
    stopScan();
  }
}

document.addEventListener("visibilitychange", function() {
  if (document.hidden) {
    stopScan();
  }
});
