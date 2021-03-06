$(function(){

	prettyPrint();

	var api_base_url = '/';
	var nav = $("#navigation");

	$('.waypoint').waypoint(function(direction) {
		if (direction === 'down') {

		var anchor = '#' + $(this).attr('name');

			$.each($('#navigation a'), function(){
				if($(this).attr('href') == anchor){
					$('#navigation li').removeClass();
					$(this).parent().addClass('active');
					$(this).parents('li').addClass('active-section');
				}
			});

		}
	}, {
	offset: '50%'
	}).waypoint(function(direction) {
		if (direction === 'up') {

			var anchor = '#' + $(this).attr('name');

			$.each($('#navigation a'), function(){
					if($(this).attr('href') == anchor){
						$('#navigation li').removeClass();
						$(this).parent().addClass('active');
						$(this).parents('li').addClass('active-section');
					}
			});
		}
	}, {
		offset: '0'
	});


	$('#search').bind('keyup', function(e) {
		var value = $.trim($(this).val());
		if (e.keyCode == 13) {
			if (value == '' || value == null) $('body').unhighlight();
			$('body').unhighlight();
			$('body').highlight(value);
		}

	});

	$('.accordion').accordion({
		collapsible: true,
		active: 1,
		heightStyle: 'content',
		change: function(e, ui){
		    $.waypoints('refresh');
		}
    });

    if ($(window).width() < 568){
    	$('.parameter-header .parameter-name').text('PARAM');
    } else {
    	$('.parameter-header .parameter-name').text('PARAMETER');
    }

	$('.api-explorer-form').on('submit', function(e){

		e.preventDefault();

		var self = $(this);

		var btn = $(this).find('.generate-response-btn');
		btn.val('Loading...');
		btn.prop('disabled', true);
		self.find('.request-uri, .response-status, .response-header, .response-body, .request').remove();

		var uri = $(this).attr('uri');

		$(':input', this).each(function() {
			name = '{' + this.name + '}';
   			uri = uri.replace(name, this.value);
  		});

        $.fn.serializeObject = function()
        {
            var o = {};
            var a = this.serializeArray();
            $.each(a, function() {
                if (o[this.name] !== undefined) {
                    if (!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        };

		var url = api_base_url + uri;
		var type = self.attr('type');
		var data_send =	self.serializeObject();
		var headers = {};

		if(data_send['authentication_ajax_value']) {
			headers['Authorization'] = data_send['authentication_ajax_value'];
            $('[name="authentication_ajax_value"]').val(data_send['authentication_ajax_value']);
		}

        delete data_send['authentication_ajax_value'];

        if (type === 'GET' && Object.keys(data_send).length) {
            data_url = Object.keys(data_send).map(function(k) {
                return encodeURIComponent(k) + '=' + encodeURIComponent(data_send[k])
            }).join('&');
            url += '?'+data_url;
            data_send = {};
        }

	 	$.ajax({
			url: url,
			type: type,
			headers: headers,
            contentType: 'application/json;charset=utf-8',
            dataType: 'json',
            data: Object.keys(data_send).length ? JSON.stringify(data_send) : null,
			success: function(data, status, request)
	        {
	        	body = JSON.stringify(JSON.parse(request.responseText), undefined, 4);
	        	statusCode = request.statusCode().status;
	        	statusText = request.statusCode().statusText;
                responseHeaders = request.getAllResponseHeaders();
                requestHeaders = $.extend(headers, {'Type': type, 'Content-Type': 'application/json;charset=utf-8', 'Data-Type': 'json'});
                if (Object.keys(data_send).length) {
                    requestHeaders['Data'] = JSON.stringify(data_send);
				}
				if(request.getResponseHeader('Authorization')) {
                	$('[name="authentication_ajax_value"]').val(request.getResponseHeader('Authorization'));
				}
	            updateResponse(self, url, body, statusCode, statusText, responseHeaders, requestHeaders);
	            enableBtn(btn);

		    },
	        error: function (response, status, request)
	        {
	        	enableBtn(btn);

	        	try {
		        	body = JSON.stringify(JSON.parse(response.responseText), undefined, 4);
	        	} catch (e){
	        		body = response.responseText;
	        	}

	        	statusCode = response.status;
	        	statusText = response.statusText;
	        	responseHeaders = response.getAllResponseHeaders();
                requestHeaders = $.extend(headers, {'Type': type, 'Content-Type': 'application/json;charset=utf-8', 'Data-Type': 'json'});
                if (Object.keys(data_send).length) {
                    requestHeaders['Data'] = JSON.stringify(data_send);
                }
                if(response.getResponseHeader('Authorization')) {
                    $('[name="authentication_ajax_value"]').val(response.getResponseHeader('Authorization'));
                }
	        	updateResponse(self,url, body, statusCode, statusText, responseHeaders, requestHeaders);
	        	enableBtn(btn);
	        }
		});

		function enableBtn(btn)
		{
			btn.prop('disabled', false);
			btn.val('Generate Example Response');
		}

		function updateResponse(self, requestUri, responseBody, statusCode, statusText, responseHeaders, requestHeaders)
		{
			self.find('.request-uri, .response-status, .response-header, .response-body').remove();
			var requestUrl = stringForResponse('Request URL', 'request-uri request', requestUri);
            requestHeaders = stringForRequest('Request', 'request', requestHeaders);
            responseHeaders = stringForResponse('Response Headers', 'response-header response', responseHeaders);
			var responseStatus = stringForResponse('Response Status', 'response-status response', statusCode + ' ' + statusText);
			responseBody = stringForResponse('Response Body', 'response-body response', responseBody);

			self.append(requestUrl);
			self.append(requestHeaders);
			self.append(responseHeaders);
			self.append(responseStatus);
			self.append(responseBody);
			prettyPrint();

			// refresh waypoints
			$.waypoints('refresh');
		}

		function stringForResponse(title, className, text)
		{
			var str = 	'<div class="' + className + '">';
			str += 		'<h3>' + title + '</h3>';
			str +=		'<div><pre class="prettyprint linenums">' + text + '</pre> </div> </div>';
			return str;

		}

		function stringForRequest(title, className, text)
		{
			var str = '';
			$.each(text, function(key, value) {
				str += key+': '+value+'\n';
			});

			return stringForResponse(title, className, str);

		}

	});

});
