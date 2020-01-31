
    var membersList = null;
    var aliasesList = null;

    // General functions

    function in_array( arr, value ) {
        var i = 0;
        while ( i < arr.length )
            if ( value == arr[i++] )
                return true;
        return false;
    }

    function addItem( list, text ) {
        list.options[list.length] = new Option( text, text, false, false );
    }

    function checkList( list ) {
        if ( list.length ) {
            list.disabled = false;
            list.options[0].selected = true;
        }
        else
            list.disabled = true;
    }

    function clearList( list ) {
        var listlen = list.length;
        for ( i = 0; i < listlen; i++ )
            list.options[0] = null;
    }

    function unmarkAll( list ) {
        for ( i = 0; i < list.length; i++ )
            list.options[i].selected = false;
    }

    function markAll( list ) {
        for ( i = 0; i < list.length; i++ )
            list.options[i].selected = true;
    }

    // sort by value
    function sortList( list ) {
        var tarr = new Array();
        var len = list.length;
        var i;

        for ( i = 0; i < len; i++ )
            tarr[i] = list.options[i].text;

        tarr.sort();
        clearList( list );

        for ( i = 0; i < len ; i++ )
            addItem( list, tarr[i] );
    }

    // Functions to work with predefined selection lists

    function moveToMembers() {
        var alen = aliasesList.length;
        var i;

        for ( i = 0; i < alen; i++ ) {
            if ( aliasesList.options[i].selected ) {
                addItem( membersList, aliasesList.options[i].value );
                aliasesList.options[i] = null;
                break;
            }
        }
        unmarkAll( membersList );
        checkList( aliasesList );
        sortList( membersList );
    }

    function addEmailToMembers( inputElement ) {
        if ( emailCheck( inputElement.value ) ) {
            addItem( membersList, inputElement.value );
            inputElement.value = '';
        }
        sortList( membersList );
    }

    function removeSelectedMembers() {
        mlen = membersList.length;
        for ( i = 0; i < mlen; ) {
            if ( membersList.options[i].selected ) {
                if ( in_array( domain_aliases, membersList.options[i].value ) )
                    addItem( aliasesList, membersList.options[i].value );
                membersList.options[i] = null;
                mlen--;
            }
            else i++;
        }

        unmarkAll( membersList );
        checkList( aliasesList );
        aliasesList.sort();
    }

    function init( members, aliases ) {
        membersList = members;
        aliasesList = aliases;
      
        clearList( membersList );
        clearList( aliasesList );

        for ( i = 0 ; i < cur_members.length; i++ )
            addItem( membersList, cur_members[i] );

        for ( i = 0 ; i < all_members.length; i++ ) {
            var member = all_members[i];
            if ( ! in_array( cur_members, member ) )
                addItem( aliasesList, member );
        }

        unmarkAll( membersList );
        unmarkAll( aliasesList );
    }

// There are function for check box processing and form submitting

    function check_boxes() {
	var i = 0;
	var val = document.forms["form"].elements["chkChangeAll"].checked;
	for (;i<document.forms["form"].elements.length; i++) {
	    if ((document.forms["form"].elements[i].type=='checkbox') && (document.forms["form"].elements[i].name!='confirm')) {
		document.forms["form"].elements[i].checked = val;
            }
	}
    }

    function do_submit( val ) {
	document.forms["form"].action.value=val;
        document.forms["form"].submit();
    }

// There are functions for for password generation

    function getRandomNum(lbound, ubound) {
	return (Math.floor(Math.random() * (ubound - lbound)) + lbound);
    }

    function getRandomChar(number, lower, upper, other, extra) {
	var numberChars = "0123456789";
	var lowerChars = "abcdefghijklmnopqrstuvwxyz";
	var upperChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var otherChars = "`~!@#$%^&*()-_=+[{]}\\|;:'\",<.>/? ";

	var charSet = extra;
	if (number == true)
	    charSet += numberChars;
	if (lower == true)
	    charSet += lowerChars;
	if (upper == true)
	    charSet += upperChars;
	if (other == true)
	    charSet += otherChars;

	return charSet.charAt(getRandomNum(0, charSet.length));
    }

    function getPassword(length, extraChars, firstNumber, firstLower, firstUpper, 
	    firstOther, latterNumber, latterLower, latterUpper, latterOther) {

	var rc = "";

	if (length > 0)
	    rc = rc + getRandomChar(firstNumber, firstLower, firstUpper, firstOther, extraChars);
	for (var idx = 1; idx < length; ++idx) {
	    rc = rc + getRandomChar(latterNumber, latterLower, latterUpper, latterOther, extraChars);
	}
	return rc;
    }
   
// for account form
    function enableRename() {
	document.form.account.disabled = false;
//	document.form.account.select();
	document.form.account.focus();
    }

    function checkInput() {
	if ( document.form.account.value == document.form.old_account.value )
	    document.form.account.disabled = true;
    } 

// for alias form
    function switchInputField(val) {
	document.forms["form"].elements["aliased_to"].disabled = val;
	document.forms["form"].elements["account_id"].disabled = val ^ 1;
    }
