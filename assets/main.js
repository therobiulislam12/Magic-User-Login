jQuery(document).ready(function ($) {
  $("#mul-user-magic-login").submit(function (e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append("action", "mul-user-login");
    formData.append("_ajax_nonce", mulSCript._ajax_nonce);

    fetch(mulSCript.ajax_url, {
      method: "POST",
      body: formData,
    })
      .then((response) => {
        return response.json();
      })
      .then((response) => {
        console.log(response);
      });
  });
});
