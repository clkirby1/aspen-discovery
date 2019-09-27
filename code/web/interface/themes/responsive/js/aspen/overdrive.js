AspenDiscovery.OverDrive = (function(){
	// noinspection JSUnusedGlobalSymbols
	return {
		cancelOverDriveHold: function(patronId, overdriveId){
			if (confirm("Are you sure you want to cancel this hold?")){
				let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=cancelHold&patronId=" + patronId + "&overDriveId=" + overdriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						if (data.success){
							AspenDiscovery.showMessage("Hold Cancelled", data.message, true);
							//remove the row from the holds list
							$("#overDriveHold_" + overdriveId).hide();
							AspenDiscovery.Account.loadMenuData();
						}else{
							AspenDiscovery.showMessage("Error Cancelling Hold", data.message, false);
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage("Error Cancelling Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
					}
				});
			}
			return false;
		},

		freezeHold: function(patronId, overDriveId){
			AspenDiscovery.loadingMessage();
			let url = Globals.path + '/OverDrive/AJAX';
			let params = {
				patronId : patronId
				,overDriveId : overDriveId
			};
			//Prompt the user for the date they want to reactivate the hold
			params['method'] = 'getReactivationDateForm'; // set method for this form
			$.getJSON(url, params, function(data){
				AspenDiscovery.showMessageWithButtons(data.title, data.modalBody, data.modalButtons)
			}).error(AspenDiscovery.ajaxFail);
		},

		// called by ReactivationDateForm when fn freezeHold above has promptForReactivationDate is set
		doFreezeHoldWithReactivationDate: function(caller){
			let popUpBoxTitle = $(caller).text() || "Freezing Hold"; // freezing terminology can be customized, so grab text from click button: caller
			let params = {
				'method' : 'freezeHold'
				,patronId : $('#patronId').val()
				,overDriveId : $('#overDriveId').val()
				,reactivationDate : $("#reactivationDate").val()
			};
			let url = Globals.path + '/OverDrive/AJAX';
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		thawHold: function(patronId, overDriveId, caller){
			let popUpBoxTitle = $(caller).text() || "Thawing Hold";  // freezing terminology can be customized, so grab text from click button: caller
			AspenDiscovery.showMessage(popUpBoxTitle, "Updating your hold.  This may take a minute.");
			let url = Globals.path + '/OverDrive/AJAX';
			let params = {
				'method' : 'thawHold'
				,patronId : patronId
				,overDriveId : overDriveId
			};
			$.getJSON(url, params, function(data){
				if (data.success) {
					AspenDiscovery.showMessage("Success", data.message, true, true);
				} else {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		setAutoCheckoutForHold: function(patronId, overDriveId, value){
			let url = Globals.path + '/OverDrive/AJAX';
			let params = {
				'method' : 'setAutoCheckoutForHold'
				,patronId : patronId
				,overDriveId : overDriveId
				,autoCheckout: value
			};
			$.getJSON(url, params, function(data){
				if (!data.success) {
					AspenDiscovery.showMessage("Error", data.message);
				}
			}).error(AspenDiscovery.ajaxFail);
		},

		getCheckOutPrompts: function(overDriveId){
			let url = Globals.path + "/OverDrive/" + overDriveId + "/AJAX?method=getCheckOutPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					result = data;
					if (data.promptNeeded){
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		checkOutTitle: function(overDriveId){
			if (Globals.loggedIn){
				//Get any prompts needed for placing holds (email and format depending on the interface.
				let promptInfo = AspenDiscovery.OverDrive.getCheckOutPrompts(overDriveId, 'hold');
				if (!promptInfo.promptNeeded){
					AspenDiscovery.OverDrive.doOverDriveCheckout(promptInfo.patronId, overDriveId);
				}
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.checkOutTitle(overDriveId);
				});
			}
			return false;
		},

		processOverDriveCheckoutPrompts: function(){
			let overdriveCheckoutPromptsForm = $("#overdriveCheckoutPromptsForm");
			let patronId = $("#patronId").val();
			let overdriveId = overdriveCheckoutPromptsForm.find("input[name=overdriveId]").val();
			AspenDiscovery.OverDrive.doOverDriveCheckout(patronId, overdriveId);
		},

		doOverDriveCheckout: function(patronId, overdriveId){
			if (Globals.loggedIn){
				let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=checkOutTitle&patronId=" + patronId + "&overDriveId=" + overdriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						if (data.success === true){
							AspenDiscovery.showMessageWithButtons("Title Checked Out Successfully", data.message, data.buttons);
							AspenDiscovery.Account.loadMenuData();
						}else{
							if (data.noCopies === true){
								AspenDiscovery.closeLightbox();
								let ret = confirm(data.message);
								if (ret === true){
									AspenDiscovery.OverDrive.placeHold(overdriveId);
								}
							}else{
								AspenDiscovery.showMessage("Error Checking Out Title", data.message, false);
							}
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
						//alert("ajaxUrl = " + ajaxUrl);
						AspenDiscovery.closeLightbox();
					}
				});
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.checkOutTitle(overdriveId);
				}, false);
			}
			return false;
		},

		doOverDriveHold: function(patronId, overDriveId, overdriveEmail, promptForOverdriveEmail, overdriveAutoCheckout){
			let url = Globals.path + "/OverDrive/AJAX?method=placeHold&patronId=" + patronId + "&overDriveId=" + overDriveId + "&overdriveEmail=" + overdriveEmail + "&promptForOverdriveEmail=" + promptForOverdriveEmail;
			if (overdriveAutoCheckout !== undefined){
				url += "&overdriveAutoCheckout=" + overdriveAutoCheckout;
			}
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.availableForCheckout){
						AspenDiscovery.OverDrive.doOverDriveCheckout(patronId, overdriveId);
					}else{
						AspenDiscovery.showMessage("Placed Hold", data.message, true);
						AspenDiscovery.Account.loadMenuData();
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage("Error Placing Hold", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
		},

		followOverDriveDownloadLink: function(patronId, overDriveId, formatId){
			let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=getDownloadLink&patronId=" + patronId + "&overDriveId=" + overDriveId + "&formatId=" + formatId;
			$.ajax({
				url: ajaxUrl,
				cache: false,
				success: function(data){
					if (data.success){
						//Reload the page
						let win = window.open(data.downloadUrl, '_blank');
						win.focus();
						//window.location.href = data.downloadUrl ;
					}else{
						alert(data.message);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
		},

		getOverDriveHoldPrompts: function(overDriveId){
			let url = Globals.path + "/OverDrive/" + overDriveId + "/AJAX?method=getHoldPrompts";
			let result = true;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					result = data;
					if (data.promptNeeded){
						AspenDiscovery.showMessageWithButtons(data.promptTitle, data.prompts, data.buttons);
					}
				},
				dataType: 'json',
				async: false,
				error: function(){
					alert("An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					AspenDiscovery.closeLightbox();
				}
			});
			return result;
		},

		placeHold: function(overDriveId){
			if (Globals.loggedIn){
				//Get any prompts needed for placing holds (email and format depending on the interface.
				let promptInfo = AspenDiscovery.OverDrive.getOverDriveHoldPrompts(overDriveId, 'hold');
				if (!promptInfo.promptNeeded){
					AspenDiscovery.OverDrive.doOverDriveHold(promptInfo.patronId, overDriveId, promptInfo.overdriveEmail, promptInfo.promptForOverdriveEmail);
				}
			}else{
				AspenDiscovery.Account.ajaxLogin(null, function(){
					AspenDiscovery.OverDrive.placeHold(overDriveId);
				});
			}
			return false;
		},

		processOverDriveHoldPrompts: function(){
			let overdriveHoldPromptsForm = $("#overdriveHoldPromptsForm");
			let patronId = $("#patronId").val();
			let overdriveId = overdriveHoldPromptsForm.find("input[name=overdriveId]").val();
			let overdriveAutoCheckout;
			if (overdriveHoldPromptsForm.find("input[name=overdriveAutoCheckout]").is(":checked")){
				overdriveAutoCheckout = 1;
			}else{
				overdriveAutoCheckout = 0;
			}
			let promptForOverdriveEmail;
			if (overdriveHoldPromptsForm.find("input[name=promptForOverdriveEmail]").is(":checked")){
				promptForOverdriveEmail = 0;
			}else{
				promptForOverdriveEmail = 1;
			}
			let overdriveEmail = overdriveHoldPromptsForm.find("input[name=overdriveEmail]").val();
			AspenDiscovery.OverDrive.doOverDriveHold(patronId, overdriveId, overdriveEmail, promptForOverdriveEmail, overdriveAutoCheckout);
		},

		renewCheckout: function(patronId, recordId){
			let url = Globals.path + "/OverDrive/AJAX?method=renewCheckout&patronId=" + patronId + "&overDriveId=" + recordId;
			$.ajax({
				url: url,
				cache: false,
				success: function(data){
					if (data.success) {
						AspenDiscovery.showMessage("Title Renewed", data.message, true);
					}else{
						AspenDiscovery.showMessage("Unable to Renew Title", data.message, true);
					}

				},
				dataType: 'json',
				async: false,
				error: function(){
					AspenDiscovery.showMessage("Error Renewing Checkout", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.", false);
				}
			});
		},

		returnCheckout: function (patronId, overDriveId){
			if (confirm('Are you sure you want to return this title?')){
				AspenDiscovery.showMessage("Returning Title", "Returning your title in OverDrive.  This may take a minute.");
				let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=returnCheckout&patronId=" + patronId + "&overDriveId=" + overDriveId;
				$.ajax({
					url: ajaxUrl,
					cache: false,
					success: function(data){
						AspenDiscovery.showMessage("Title Returned", data.message, data.success);
						if (data.success){
							$(".overdrive_checkout_" + overDriveId).hide();
							AspenDiscovery.Account.loadMenuData();
						}
					},
					dataType: 'json',
					async: false,
					error: function(){
						AspenDiscovery.showMessage("Error Returning Title", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
					}
				});
			}
			return false;
		},

		selectOverDriveDownloadFormat: function(patronId, overDriveId){
			let selectedOption = $("#downloadFormat_" + overDriveId + " option:selected");
			let selectedFormatId = selectedOption.val();
			let selectedFormatText = selectedOption.text();
			// noinspection EqualityComparisonWithCoercionJS
			if (selectedFormatId == -1){
				alert("Please select a format to download.");
			}else{
				if (confirm("Are you sure you want to download the " + selectedFormatText + " format? You cannot change format after downloading.")){
					let ajaxUrl = Globals.path + "/OverDrive/AJAX?method=selectOverDriveDownloadFormat&patronId=" + patronId + "&overDriveId=" + overDriveId + "&formatId=" + selectedFormatId;
					$.ajax({
						url: ajaxUrl,
						cache: false,
						success: function(data){
							if (data.success){
								//Reload the page
								window.location.href = data.downloadUrl;
							}else{
								AspenDiscovery.showMessage("Error Selecting Format", data.message);
							}
						},
						dataType: 'json',
						async: false,
						error: function(){
							AspenDiscovery.showMessage("Error Selecting Format", "An error occurred processing your request in OverDrive.  Please try again in a few minutes.");
						}
					});
				}
			}
			return false;
		}
	}
}(AspenDiscovery.OverDrive || {}));