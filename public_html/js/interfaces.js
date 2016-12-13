/**
 * Created by morussa on 5/11/2016.
 */
var newEmpXRef, EmpXRefMessage, EmpXRefContainer, JobXRefContainer, newJobXRef, newJobCode, JobXRefMessage, newMasterLevelEmpXRef, newMasterLevel, MasterLevelEmpXRefMessage, MasterLevelEmpXRefContainer, OtherTablesExistingTablesContainer, SunLifeAddRecordMessage, SunLifeRatesContainer, SunLifeMessageContainer;

function submitEnable() {
    console.log('enable submit');
    $('#loginSubmit').attr('disabled', false);
}

function submitDisable() {
    console.log('disable submit');
    $('#loginSubmit').attr('disabled', true);
}

$(document).ready(function () {
    newEmpXRef = $("#newEmpXRef"), EmpXRefMessage = $("#EmpXRefMessage"), EmpXRefContainer = $("#EmpXRefContainer"), JobXRefContainer = $("#JobXRefContainer"), newJobXRef = $("#newJobXRef"), newJobCode = $("#newJobCode"), JobXRefMessage = $("#JobXRefMessage"), newMasterLevelEmpXRef = $("#newMasterLevelEmpXRef"), newMasterLevel = $("#newMasterLevel"), MasterLevelEmpXRefMessage = $("#MasterLevelEmpXRefMessage"), MasterLevelEmpXRefContainer = $("#masterLevelEmpXRefContainer"), OtherTablesExistingTablesContainer = $("#otherTableExistingTablesContainer"), SunLifeAddRecordMessage = $("#sunLifeAddRecordMessage"), SunLifeRatesContainer = $("#sunLifeRatesContainer"), SunLifeMessageContainer = $("#sunLifeMessageContainer");

    /*if (message.html() != '') {
        message.prepend(closeButton);
        message.show();
    } else {
        message.html(closeButton);
    }*/

    /*
     Use cookies to detect when the files have been served and then remove the iframes.
     It looks for all elements with the class of hiddenFileDownload and looks to see if there is a cookie that has the id of the element. If the element and the cookie exist it will remove both.
     */
    setTimeout(function () {
            $(".hiddenFileDownload").each(function (index, value) {
                var currentId = $(this).attr('id');
                $(this).remove();
                //console.log('cookiepath: ' + cookiepath + ', cookiedomain: ' + cookiedomain);
                setCookie(currentId, "", -1, cookiepath, cookiedomain);
                //console.log('currentId: ' + currentId);
            });
        }, 3000
    );

    Page.on("click", "#addNewEmpXRef", function () {
        addEmpXRef();
    });

    Page.on("click", "#addNewJobXRef", function () {
        addJobXRef();
    });

    Page.on("click", "#addNewMasterLevelEmpXRef", function () {
        addMasterLevelEmpXRef();
    });

    Page.on("click", ".deleteEmpXRef", function () {
        var EmpXRef = $(this).attr('data-empxref');
        if (confirm('Are you sure you want to delete this EmpXRef?')) {
            deleteEmpXRef(EmpXRef);
        }
    });

    Page.on("click", ".deleteJobXRef", function () {
        var JobXRef = $(this).attr('data-jobxref');
        if (confirm('Are you sure you want to delete this JobXRef?')) {
            deleteJobXRef(JobXRef);
        }
    });

    Page.on("click", ".deleteMasterLevelEmpXRef", function () {
        var EmpXRef = $(this).attr('data-empxref');
        if (confirm('Are you sure you want to delete this EmpXRef?')) {
            deleteMasterLevelEmpXRef(EmpXRef);
        }
    });

    Page.on("click", "#logout", function () {
        var mode = '';
        logout();
    });

    Page.on("click", "#loginSubmit", function () {
        var passwordField = $("input[name='password']");
        if ($("input[name='password']").val() != '') {
            login();
        } else {
            console.log('password empty');
            $("#loginError").html('Please enter the password.');
            passwordField.focus();
        }
    });

    Page.on("keypress", "input[name='password']", function (e) {
        if (e.which == 13) {
            console.log('buddy');
            e.preventDefault();
            $("#loginSubmit").click();
        }
    });

    Page.on("click", ".otherTableAddColumn", function () {
        // Add a column.
        //otherTableAddColumn();
        var thingToAppend = $(".otherTableRowSupply").find("tr").clone();
        $("#otherTableTbody").append(thingToAppend);
        $(".otherTableDeleteColumn").removeClass("hide");
    });

    Page.on("click", ".otherTableDeleteColumn", function () {
        // Delete a column.
        $(this).parent().parent().remove();
        if ($(".otherTableAddColumn").length == 2) {
            $(".otherTableDeleteColumn").addClass("hide");
        }
    });

    Page.on("click", "#otherTableAddTable", function () {
        // Add a table.
        otherTableAddTable();
    });

    Page.on("click", ".otherTableDeleteTable", function () {
        var tableName = $(this).attr('data-tablename');
        if (confirm('Are you sure you want to delete this table?')) {
            otherTableDeleteTable(tableName);
        }
    });

    Page.on("click", "#sunLifeAddRecordButton", function () {
        // Add a table.
        sunLifeAddRecordButton();
    });

    Page.on("click", ".sunLifeDeleteRate", function () {
        var rateId = $(this).attr('data-id');
        if (confirm('Are you sure you want to delete this rate?')) {
            sunLifeDeleteRate(rateId);
        }
    });

    Page.on("click", ".sunLifeUpdateButton", function () {
        // Add a table.
        sunLifeUpdateButton($(this).attr('data-id'));
    });

    function addEmpXRef() {
        EmpXRefMessage.html("").show();
        if (newEmpXRef.val() == '') {
            EmpXRefMessage.html("Please enter an EmpXRef code.");
        } else {
            $.ajax({
                type: 'post',
                url: url,
                data: {
                    'mode': 'newEmpXRef',
                    'EmpXRef': newEmpXRef.val()
                },
                beforeSend: function () {
                    spinnerShow('adding employee…');
                },
                error: function () {
                    spinnerShow('error add new EmpXRef');
                },
                success: function (result) {
                    result = $.parseJSON(result);
                    var message = result.message ? result.message : '';
                    coverMe();
                    EmpXRefMessage.html(message);
                    if (result.success == true) {
                        newEmpXRef.val("");
                        EmpXRefMessage.delay(1500).fadeOut(500, function () {
                            EmpXRefMessage.hide();
                        });
                        var list = result.list ? result.list : 'error return list of EmpXRef';
                        EmpXRefContainer.html(list);
                    }
                    if (result.debug) {
                        debugElement.html(result.debug);
                    }
                }
            })
        }
    }

    function addJobXRef() {
        JobXRefMessage.html("").show();
        if (newJobXRef.val() == '') {
            JobXRefMessage.html("Please enter a JobXRef code.");
        } else if (newJobCode.val() == '') {
            JobXRefMessage.html("Please enter a JobCode.");
        } else {
            $.ajax({
                type: 'post',
                url: url,
                data: {
                    'mode': 'newJobXRef',
                    'JobXRef': newJobXRef.val(),
                    'JobCode': newJobCode.val()

                },
                beforeSend: function () {
                    spinnerShow('adding JobXRef…');
                },
                error: function () {
                    spinnerShow('error add new JobXRef');
                },
                success: function (result) {
                    result = $.parseJSON(result);
                    var message = result.message ? result.message : 'error return add new JobXRef'
                    JobXRefMessage.html(message);
                    coverMe();
                    if (result.success == true) {
                        newJobXRef.val("");
                        newJobCode.val("");
                        JobXRefMessage.delay(1500).fadeOut(500, function () {
                            JobXRefMessage.hide();
                        });
                        var list = result.list ? result.list : 'error return list of JobXRef';
                        JobXRefContainer.html(list);
                    }
                    if (result.debug) {
                        debugElement.html(result.debug);
                    }
                }
            })
        }
    }

    function addMasterLevelEmpXRef() {
        MasterLevelEmpXRefMessage.html("").show();
        if (newMasterLevelEmpXRef.val() == '') {
            MasterLevelEmpXRefMessage.html("Please enter a EmpXRef code.");
        } else {
            $.ajax({
                type: 'post',
                url: url,
                data: {
                    'mode': 'newMasterLevelEmpXRef',
                    'masterLevelEmpXRef': newMasterLevelEmpXRef.val(),
                    'masterLevel': newMasterLevel.val()
                },
                beforeSend: function () {
                    spinnerShow('adding master level EmpXRef…');
                },
                error: function () {
                    spinnerShow('error add new master level EmpXRef');
                },
                success: function (result) {
                    result = $.parseJSON(result);
                    var message = result.message ? result.message : 'error return add new master level EmpXRef';
                    MasterLevelEmpXRefMessage.html(message);
                    coverMe();
                    if (result.success == true) {
                        newMasterLevelEmpXRef.val("");
                        newMasterLevel.val("");
                        MasterLevelEmpXRefMessage.delay(1500).fadeOut(500, function () {
                            MasterLevelEmpXRefMessage.hide();
                        });
                        var list = result.list ? result.list : 'error return list master level EmpXRef';
                        MasterLevelEmpXRefContainer.html(list);
                    }
                    if (result.debug) {
                        debugElement.html(result.debug);
                    }
                }
            })
        }
    }

    function buildLogin() {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'buildLogin'
            },
            beforeSend: function () {
                spinnerShow('working…');
            },
            error: function () {
                spinnerShow('error build login');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                if (result.success == true) {
                    var buildLogin = result.buildLogin ? result.buildLogin : 'error build login';
                    coverMe(buildLogin);
                    $("input[name='password']").focus();
                } else {
                    showMessage(message);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function deleteEmpXRef(EmpXRef) {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'deleteEmpXRef',
                'EmpXRef': EmpXRef
            },
            beforeSend: function () {
                spinnerShow('deleting EmpXRef…');
            },
            error: function () {
                spinnerShow('error delete EmpXRef');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                if (result.success == true) {
                    var list = result.list ? result.list : 'error delete EmpXRef';
                    EmpXRefContainer.html(list);
                    showMessage(message, true);
                } else {
                    showMessage(message);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function deleteJobXRef(JobXRef) {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'deleteJobXRef',
                'JobXRef': JobXRef
            },
            beforeSend: function () {
                spinnerShow('deleting JobXRef…');
            },
            error: function () {
                spinnerShow('error delete JobXRef');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                if (result.success == true) {
                    var list = result.list ? result.list : 'error delete JobXRef';
                    JobXRefContainer.html(list);
                    showMessage(message, true);
                } else {
                    showMessage(message);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function deleteMasterLevelEmpXRef(EmpXRef) {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'deleteMasterLevelEmpXRef',
                'EmpXRef': EmpXRef
            },
            beforeSend: function () {
                spinnerShow('deleting Master Level EmpXRef…');
            },
            error: function () {
                spinnerShow('error delete master level EmpXRef');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                if (result.success == true) {
                    var list = result.list ? result.list : 'delete master level EmpXRef';
                    MasterLevelEmpXRefContainer.html(list);
                    showMessage(message, true);
                } else {
                    showMessage(message);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function listEmpXRef() {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'listEmpXRef'
            },
            beforeSend: function () {
                spinnerShow('fetching EmpXRef list…');
            },
            error: function () {
                spinnerShow('error list EmpXRef');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                EmpXRefContainer.html(message);
                if (result.success == true) {
                    var list = result.list ? result.list : 'error return list EmpXRef';
                    EmpXRefContainer.html(list);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function listJobXRef() {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'listJobXRef'
            },
            beforeSend: function () {
                spinnerShow('fetching JobXRef…');
            },
            error: function () {
                spinnerShow('error list JobXRef');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                JobXRefContainer.html(message);
                if (result.success == true) {
                    var list = result.list ? result.list : 'error return list JobXRef';
                    JobXRefContainer.html(list);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function listMasterLevelEmpXRef() {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'listMasterLevelEmpXRef'
            },
            beforeSend: function () {
                spinnerShow('fetching master level EmpXRef…');
            },
            error: function () {
                spinnerShow('error list master level EmpXRef');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                MasterLevelEmpXRefContainer.html(message);
                if (result.success == true) {
                    var list = result.list ? result.list : 'error return list master level EmpXRef';
                    MasterLevelEmpXRefContainer.html(list);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function login() {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'login',
                'password': $("input[name='password']").val()
            },
            beforeSend: function () {
                spinnerShow('working…');
            },
            error: function () {
                spinnerShow('error login');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                if (result.success == true) {
                    window.location.href = './';
                } else {
                    spinner.hide();
                    $("#loginError").html(message);
                    console.log(message);
                    //window.location.href = returnUrl;
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function logout() {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'logout'
            },
            beforeSend: function () {
                spinnerShow('working…');
            },
            error: function () {
                spinnerShow('error logout');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                if (result.success == true) {
                    // location.reload();
                    window.location.href = './';
                } else {
                    showMessage(message);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function otherTableAddColumn() {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'otherTableAddColumn'
            },
            beforeSend: function () {
                spinnerShow('working…');
            },
            error: function () {
                spinnerShow('error other table add column');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                if (result.success == true) {
                    $(".otherTableAddColumn").remove();
                    $(".otherTableDeleteColumn").removeClass("hide");
                    $("#otherTableTbody").append(result.otherTableAddColumn ? result.otherTableAddColumn : 'error return other table add column');
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function otherTableAddTable() {
        // Build the data to send via AJAX.
        var problem = '';
        if ($("#otherTableTableName").val() == '') {
            // Check for a table name.
            $("#otherTableTableName").focus();
            problem += '<div class="error">Please enter a name for the table.</div>';
        }
        if (checkForSpaces($("#otherTableTableName").val())) {
            problem += '<div class="error">The table name cannot contain spaces.</div>';
        }
        var data = 'mode=otherTableAddTable&otherTableTableName=' + $("#otherTableTableName").val();
        $("#otherTableTbody tr.hasData").each(function (index) {
            // Loop through the table rows and get the values of the various form elements.
            console.log(index + ": " + $(this).html());
            data = data + '&otherTableDataType' + index + '=' + $("td select.otherTableDataType", this).val() +
                '&otherTableColumnName' + index + '=' + $("td input.otherTableColumnName", this).val() +
                '&otherTableAllowNull' + index + '=' + $("td select.otherTableAllowNull", this).val();

            // Check that we have values for the data type and column name.
            if ($("td select.otherTableDataType", this).val() == 'Select') {
                problem += '<div class="error">Please select a data type for all columns.</div>';
            }
            if ($("td input.otherTableColumnName", this).val() == '') {
                problem += '<div class="error">Please enter a name for all columns.</div>';
            }
            if (checkForSpaces($("td input.otherTableColumnName", this).val())) {
                problem += '<div class="error">Column names cannot have spaces.</div>';
                $("td input.otherTableColumnName", this).focus();
            }
        });
        console.log('data: ' + data);
        if (!problem) {
            // Use ajax to pass our data to the server.
            $.ajax({
                type: 'post',
                url: url,
                data: data,
                beforeSend: function () {
                    spinnerShow('working…');
                },
                error: function () {
                    spinnerShow('error other table add table');
                },
                success: function (result) {
                    result = $.parseJSON(result);
                    var message = result.message ? result.message : '';
                    coverMe();
                    if (result.success == true) {
                        $("#otherTableProblem").hide();
                        var list = result.list ? result.list : 'error other table add table';
                        OtherTablesExistingTablesContainer.html(list);
                    }
                    if (result.debug) {
                        debugElement.html(result.debug);
                    }
                }
            })
        } else {
            $("#otherTableProblem").html(problem);
        }
    }

    function otherTableDeleteTable(tableName) {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'otherTableDeleteTable',
                'tableName': tableName
            },
            beforeSend: function () {
                spinnerShow('deleting other table…');
            },
            error: function () {
                spinnerShow('error delete other table');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                if (result.success == true) {
                    var list = result.list ? result.list : 'error delete other table';
                    OtherTablesExistingTablesContainer.html(list);
                    showMessage(message, true);
                } else {
                    showMessage(message);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function sunLifeAddRecordButton() {
        // Build the data to send via AJAX.
        var data = 'mode=sunLifeAddRecord';
        var errors = [];

        // Validate the input fields. Add errors to an array.
        $("#sunLifeNewRecordTable input").each(function (index) {
            var field = $(this).attr('id');
            var fieldVal = $(this).val();
            var dataType = $(this).attr('data-type');
            var dataName = $(this).attr('data-name');
            if (dataType == 'string' && fieldVal.length > 256) {
                errors.push(dataName + ' must be 256 characters or less.')
            } else if (dataType == 'integer' && fieldVal != parseInt(fieldVal, 10)) {
                errors.push(dataName + ' must be an integer.')
            } else if (dataType == 'decimal' && !isNumeric(fieldVal)) {
                errors.push(dataName + ' must be numeric.')
            } else if (dataType == 'decimal' && fieldVal.replace(/\D/g, '').length > 5) {
                // Must be 5 or less digits, not including the decimal point.
                errors.push(dataName + ' must be less than 6 digits.')
            }
            // URL encode the data.
            data += '&' + field + '=' + encodeURIComponent($("#" + field).val());
        })

        // Show the errors.
        var errorLength = errors.length;
        SunLifeAddRecordMessage.empty()
        if (errorLength > 0) {
            for (var i = 0; i < errorLength; i++) {
                SunLifeAddRecordMessage.append(errors[i] + '<br>');
            }
        }
        // Send the AJAX command.
        if (errors.length == 0) {
            // Use ajax to pass our data to the server.
            $.ajax({
                type: 'post',
                url: url,
                data: data,
                beforeSend: function () {
                    spinnerShow('working…');
                },
                error: function () {
                    spinnerShow('error sun life add record');
                },
                success: function (result) {
                    result = $.parseJSON(result);
                    var message = result.message ? result.message : '';
                    coverMe();
                    if (result.success == true) {
                        var list = result.list ? result.list : 'error sun life add record list';
                        SunLifeRatesContainer.html(list);
                        showMessage(message, true);
                    } else {
                        SunLifeAddRecordMessage.html(message);
                    }
                    if (result.debug) {
                        debugElement.html(result.debug);
                    }
                }
            })
        } else {
            $("#otherTableProblem").html(problem);
        }
    }

    function sunLifeDeleteRate(rateId) {
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'mode': 'sunLifeDeleteRate',
                'rateId': rateId
            },
            beforeSend: function () {
                spinnerShow('deleting rate…');
            },
            error: function () {
                spinnerShow('error delete rate');
            },
            success: function (result) {
                result = $.parseJSON(result);
                var message = result.message ? result.message : '';
                coverMe();
                if (result.success == true) {
                    var list = result.list ? result.list : 'error delete rate';
                    SunLifeRatesContainer.html(list);
                    showMessage(message, true);
                } else {
                    showMessage(message);
                }
                if (result.debug) {
                    debugElement.html(result.debug);
                }
            }
        })
    }

    function sunLifeUpdateButton(id) {
        // Save changes to the data.
        console.log('clicked sunLifeUpdateButton');
        var data = 'mode=sunLifeUpdate&id=' + id;
        var errors = [];

        $(".sunLifeEdit[data-id='" + id + "']").children('td').children('.sunLifeInput').each(function (index, c) {
            // console.log('In loop: ' + $(c).val());
            var fieldVal = $(this).val();
            var dataType = $(this).attr('data-type');
            var dataName = $(this).attr('data-name');
            var dataParameterName = $(this).attr('data-parametername');
            if (dataType == 'string' && fieldVal.length > 256) {
                errors.push(dataName + ' must be 256 characters or less.')
            } else if (dataType == 'integer' && fieldVal != parseInt(fieldVal, 10)) {
                errors.push(dataName + ' must be an integer.')
            } else if (dataType == 'decimal' && !isNumeric(fieldVal)) {
                errors.push(dataName + ' must be numeric.')
            } else if (dataType == 'decimal' && fieldVal.replace(/\D/g, '').length > 5) {
                // Must be 5 or less digits, not including the decimal point.
                errors.push(dataName + ' must be less than 6 digits.')
            }
            // URL encode the data.
            data += '&' + dataParameterName + '=' + encodeURIComponent(fieldVal);
        });
        console.log('data: ' + data);
        // Validate the input fields. Add errors to an array.

        // Show the errors.
        var errorLength = errors.length;
        SunLifeMessageContainer.empty()
        if (errorLength > 0) {
            for (var i = 0; i < errorLength; i++) {
                SunLifeMessageContainer.append(errors[i] + '<br>');
            }
        }
        // Send the AJAX command.
        if (errors.length == 0) {
            // Use ajax to pass our data to the server.
            $.ajax({
                type: 'post',
                url: url,
                data: data,
                beforeSend: function () {
                    spinnerShow('working…');
                },
                error: function () {
                    spinnerShow('error sun life update');
                },
                success: function (result) {
                    result = $.parseJSON(result);
                    var message = result.message ? result.message : '';
                    coverMe();
                    if (result.success == true) {
                        var list = result.list ? result.list : 'error sun life update list';
                        SunLifeRatesContainer.html(list);
                    }
                    SunLifeMessageContainer.html(message);
                    if (result.debug) {
                        debugElement.html(result.debug);
                    }
                }
            })
        } else {
            $("#otherTableProblem").html(problem);
        }
    }

});