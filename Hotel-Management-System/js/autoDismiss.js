// Auto-dismiss alert after 3 seconds
document.addEventListener("DOMContentLoaded", function () {
  const autoAlert = document.getElementById("autoAlert");
  if (autoAlert) {
    setTimeout(function () {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(autoAlert);
      bsAlert.close();
    }, 3000);
  }
});
