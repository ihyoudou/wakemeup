document.addEventListener("DOMContentLoaded", function(event) { 
    getURLcount().then(count => {
        document.getElementById("pingcount").innerHTML = '<p class="text-muted fw-light">pinging '+count['count']+' urls</p>';
      });
    document.getElementById("addButton").addEventListener("click", function() {
        document.getElementById('spinner').innerHTML = '<div class="spinner-border" role="status"></div>';
        let hcaptchaResult = document.getElementsByName("g-recaptcha-response")[0].value;
        let url = document.getElementById('url').value;
        document.getElementById("addButton").disabled = true; 
        (async () => {
            const rawResponse = await fetch('/api/v1/addURL', {
                method: 'POST',
                headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
                },
                body: JSON.stringify({url: url, captchaResponse: hcaptchaResult})
            });
            const content = await rawResponse.json();

            console.log(content);
            
            if(content['success'] == true){
                document.getElementById("alerts").innerHTML = '<div class="alert alert-success" role="alert">Your URL was added!</div>';
                document.getElementById("url").value = '';
                // alert("Your URL was added!");
            } else {
                hcaptcha.reset();
                if(content['reason'] == "urlExist"){
                    document.getElementById("alerts").innerHTML = '<div class="alert alert-warning" role="alert">This URL is already in database!</div>';
                    // alert("This URL is already in database!");
                } else if(content['reason'] == "missingBodyOrBadURL"){
                    document.getElementById("alerts").innerHTML = '<div class="alert alert-warning" role="alert">The URL your entered is invalid</div>';
                    // alert("The URL your entered is invalid");
                } else if(content['reason'] == "captchaInvalid"){
                    document.getElementById("alerts").innerHTML = '<div class="alert alert-warning" role="alert">Captcha result was invalid, please try again</div>';
                    // alert("Captcha result was invalid, please try again");
                } else {
                    alert("Unknown error, please try again later");
                }
            }
            document.getElementById('spinner').innerHTML = '';
            document.getElementById("addButton").disabled = false; 
        })();
        
    }); 
});

async function getURLcount(){
    const response = await fetch('/api/v1/getURLcount');
    const count = await response.json();
    return count;
}