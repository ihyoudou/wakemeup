document.addEventListener("DOMContentLoaded", function(event) { 
    getURLcount().then(count => {
        document.getElementById("pingcount").innerHTML = '<p class="text-muted fw-light">pinging '+count['count']+' urls</p>';
      });
    document.getElementById("addButton").addEventListener("click", function() {
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
                alert("Your URL was added!");
            } else {
                if(content['reason'] == "urlExist"){
                    alert("This URL is already in database!");
                } else if(content['reason'] == "missingBodyOrBadURL"){
                    alert("The URL your entered is invalid");
                } else if(content['reason'] == "captchaInvalid"){
                    alert("Captcha result was invalid, please try again");
                } else {
                    alert("Unknown error, please try again later");
                }
            }
            document.getElementById("addButton").disabled = false; 
        })();
        
    }); 
});

async function getURLcount(){
    const response = await fetch('/api/v1/getURLcount');
    const count = await response.json();
    return count;
}