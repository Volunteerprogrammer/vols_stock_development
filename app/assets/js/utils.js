/*============================================================================================================================*/
/*                                                      UTILITIES                                                             */
/*============================================================================================================================*/
function fw_DownloadDataFile(filename, data) {
    const blob = new Blob([data], {type: 'text/csv'});
    if(window.navigator.msSaveOrOpenBlob) {
        window.navigator.msSaveBlob(blob, filename);
    } else{
        const elem = window.document.createElement('a');
        elem.href = window.URL.createObjectURL(blob);
        elem.download = filename;
        document.body.appendChild(elem);
        elem.click();
        document.body.removeChild(elem);
    }
}
function fw_validate_integerfield(elem,allow_wildcards,callbackmsg) {
    var dat = elem.val();
    const regex = RegExp("[0-9%_]+");
    if (dat !== "" && allow_wildcards && regex.test(dat)) {
        elem.parent().prev().css("background", "#fff");
        callbackmsg("");
    } else if (dat !== "" && isNaN(dat)) {
        elem.parent().prev().css("background", "#fee");
        elem.focus();
        callbackmsg("This field must contain a Number");
    } else {
        elem.parent().prev().css("background", "#fff");
        callbackmsg("");
    }
}

function fw_Win1252CharToUTF8(n){
    // used to convert win1252 chars in the range 128-150 to their ASCII
    // equivalents - only deals with chars with reasonable equivalents
    // - others return 63 = "?". Needed during import of arts catalogue
    // data by jQuery("#-editor-loadfile") (DT Mar 22)
    switch (n) {
        case 130: return 44;
        case 133: return 46;
        case 136: return 94;
        case 145: return 39;
        case 146: return 39;
        case 147: return 34;
        case 148: return 34;
        case 150: return 45;
        case 151: return 45;
        default:  return 63;
    }
}
function fw_removeDuplicates(array) {
    var x = {};
    array.forEach(function fw_(i) {
        if (!x[i]) {
            x[i] = true;
        }
    });
    return Object.keys(x);
}

function fw_substring_count(mainstring, substring) {
    mainstring += '';
    substring += '';
    if (substring.length <= 0)     {
        return 0;
    }
    return (mainstring.match(new RegExp(substring, 'gi')) || []).length;
}

function fw_reverse_date_format(d,sep1,sep2) {
    // converts e.g. yyyy-mm-dd to dd/mm/yyyy, or back the other way
    var parts = d.split(sep1);
    return parts[2]+sep2+parts[1]+sep2+parts[0];
}
function fw_HasAttribute(elem,attrname) {
var attr = elem.attr(attrname);
return (typeof attr !== typeof undefined && attr !== false);
}
function fw_B64Encode(str) {
    // first we use encodeURIComponent to get percent-encoded UTF-8,
    // then we convert the percent encodings into raw bytes which
    // can be fed into btoa.
    return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
        function fw_toSolidBytes(match, p1) {
            return String.fromCharCode('0x' + p1);
    }));
    // return window.btoa(unescape(encodeURIComponent(str))); //(unescape(encodeURIComponent())
}
function fw_atob(str) {
    return decodeURIComponent(atob(str.replace(/&#x2F;/g,"/")).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
}
function fw_B64Decode(str,clog=0,tag="") {
    if (str !== "" && clog === 1) {
        console.log(tag,B64HTMLUnescape(str));
    }
    return str !== "" ? UTF8ArrayToStr(atob(B64HTMLUnescape(str))) : "";
}
function fw_B64HTMLUnescape($str) {
    // called by B64Decode(), above - only one (rarely seen) b64 char ("/")
    // will ever have been html_escaped (to &#x2F;) so just fix that
    $str = $str.replace(/&#x2F;/g,'/');
    return $str;
}
function fw_HTMLEscape($str) {
  if (typeof $str === "string") {
    return $str.replace(/\"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/'/g,'&apos;').replace(/&/g,'&amp;').replace(/\//g,'&#x2F;');
  } else {
    return "";
  }
}
function fw_cl(){
    console.log (arguments)
}
function fw_JSONEscape(instr) {
  var s ;
  s = instr.replace(/\\/g,'\\\\').replace(/"/g,'\\"').replace(/\{/g,'\\{').replace(/}/g,'\\}').replace(/\[/g,'\\[').replace(/]/g,'\\]').replace(/,/g,'comma');
  return s;
}
function fw_JSONUnescape($str) {
  var s ;
  s = $str.replace('comma',',').replace('\\"','"').replace('\\]',']').replace('\\[','[').replace('\\{','{').replace('\\}','}').replace('\\\\','\\');
  return s;
}
function fw_HTMLDecodeJSON(input) {
  var doc;
  try {
      doc = new DOMParser().parseFromString(input,"text/xml");
  } catch (e) {
      // if text is not well-formed,
      // it raises an exception in IE from version 9
      alert ("Parsing error.");
      return false;
  }
  return doc.documentElement.textContent;
}
function fw_ConvertToQuotedString(arr) {
  var s = '';
  if (Array.isArray(arr)) {
    arr.forEach ((fld)=>{
      s += '"' + fld + '",';
    });
  } else {
    s = arr;
  }
  return "["+s+"]";
}
function fw_LocalStorageSave(key,value) {
  if (typeof value !== "string") {
    value = value;
  }
  if (typeof value === "string" ) {
    localStorage.setItem(key , value);
  }
}
function fw_LocalStorageRetrieve(key) {
  return localStorage.getItem(key);
}
function fw_LocalStorageDelete(key) {
  localStorage.removeItem(key);
}
function fw_ToClipboard(thetext) {
  var textArea = document.createElement("textarea");
  textArea.value = thetext;
  // Avoid scrolling to bottom
  textArea.style.top = "0";
  textArea.style.left = "0";
  textArea.style.position = "fixed";
  document.body.appendChild(textArea);
  textArea.focus();
  textArea.select();
  try {
    var successful = document.execCommand('copy');
    var msg = successful ? 'successful' : 'unsuccessful';
    //console.log('Copying text command was ' + msg);
  } catch (err) {
    //console.error('Fallback: Oops, unable to copy', err);
  }
  document.body.removeChild(textArea);
}
function fw_GetActualBackgroundColor(elem) {
    while (elem && window.getComputedStyle(elem).backgroundColor === "rgba(0, 0, 0, 0)") {
        elem = elem.parentNode;
    }
    return elem ? window.getComputedStyle(elem).backgroundColor : "transparent";
}
/* URI CHARS:   ! * ' ( ) ; : @ & = + $ , / ? # [ ]    FORMAT: %xx                                     */
/* HTML CHARS:  &<>"'/                                 FORMAT: &amp; &lt; &gt; &quot; &apos; &#x2F;    */
/* JS CHARS:   all non-alphanumeric                    FORMAT: & = \x26                                */
/* REGEX CHARS:   \^$.|?*+()[{                         FORMAT: backslash escaping                      */
function fw_containsNonLatinCodepoints(s) {
    return /[^\u0000-\u00ff]/.test(s);
}
function fw_UTF8ArrayToStr(array) {
    var out, i, len, c;
    var char2, char3;
    out = "";
    len = array.length;
    i = 0;
    while(i < len) {
        c = array[i++];
        switch(c >> 4) {
            case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7:
                // 0xxxxxxx
                out += c;// String.fromCharCode(c);
                break;
            case 12: case 13:
                // 110x xxxx   10xx xxxx
                char2 = array[i++];
                out += String.fromCharCode(((c & 0x1F) << 6) | (char2 & 0x3F));
                break;
            case 14:
                // 1110 xxxx  10xx xxxx  10xx xxxx
                char2 = array[i++];
                char3 = array[i++];
                out += String.fromCharCode(((c & 0x0F) << 12) |
                               ((char2 & 0x3F) << 6) |
                               ((char3 & 0x3F) << 0));
                break;
        }
    }
    return out;
}
function fw_ObjDump(obj) {
  var out = '';
  for (var i in obj) {
      out += i + ": " + obj[i] + "\n";
  }
  return out;
}
Disable = function ($element, $opacity) {
    if ($opacity === undefined) {
        $opacity = "0.3";
    }
    jQuery($element).css("pointer-events", "none");
    jQuery($element).css("opacity", $opacity);
}
Enable = function ($element) {
    jQuery($element).css("pointer-events", "");
    jQuery($element).css("opacity", 1);
}
function fw_GetTarget(str,ti) {
  var opentarget = '"\'{[', closetarget = '"\'}]';
  var ipos = opentarget.indexOf(str[ti]);
  if (ipos !== -1 && (ti === 0 || str[ti - 1] !== "\\" )) {
     return closetarget[ipos];
  } else {
    return "";
  }
}
function fw_FindMatch(str,mi,target) {
  var s = mi;
  while ((str[mi] != target || (mi !== 0 && str[mi-1] === "\\")) && mi <= str.length) {
    var newtarget = GetTarget(str, mi);
    if (newtarget !== "") {
      mi = FindMatch(str,mi+1,newtarget)
    }
    mi++;
  }
  return mi;
}
function fw_StringArrayPut(str,sep,segment,value) {
  // adds content to a data position in a delimited string
  // where str is the string, sep is the delimiter, segment id the data record to update (starting with 0) and value is...
  // e.g. if s="0,234,5,"sadf",[1,2,3],ABC," a call with params (s,",",5,"xxx") produces "0,234,5,"sadf",[1,2,3],xxx,"
  var i = 0, sepcount = 0, startpos = -1, endpos = -1,result;
  if (str  !== undefined) {
    while (i <= str.length) {
      if (str.substring(i).startsWith(sep)) {
        sepcount++;
      } else {
        var target = GetTarget(str, i);
        if (target !== "") {
          i = FindMatch(str,i+1,target)
        }
      }
      if (sepcount >= segment) {
        if (startpos === -1) {
          startpos = i + (sepcount>0?1:0);
        }
        if (sepcount > segment)  {
          endpos = i;
          break;
        }
      }
      i++;
    }
    if (endpos === -1) {
      endpos = str.length;
    }
    result = (endpos=== 0 ? "":str.slice(0,startpos))+value+str.slice(endpos);
    return result;
  } else {
    return "";
  }
}
function fw_StringArrayGet(str,sep,segment) {
  // returne the content of a data position in a delimited string
  // where str is the string, sep is the delimiter, segment id the data record to return
  // e.g. if s="0,234,5,"sadf",[1,2,3],ABC," a call with params (s,",",3)  returns '"sadf"'
  var i = 0, sepcount = 0, startpos = -1, endpos = -1,result;
  if (str  !== undefined) {
    while (i <= str.length) {
      if (str.substring(i).startsWith(sep)) {
        sepcount++;
      } else {
        var target = GetTarget(str, i);
        if (target !== "") {
          i = FindMatch(str,i+1,target)
        }
      }
      if (sepcount >= segment) {
        if (startpos === -1) {
          startpos = i + (sepcount>0?1:0);
        }
        if (sepcount > segment)  {
          endpos = i;
          break;
        }
      }
      i++;
    }
    if (endpos === -1) {
      endpos = str.length;
    }
    return str.slice(startpos,endpos);
  } else {
    return "";
  }
}
function fw_StringParseToObjOrArray(str,sep,objectwanted=0,notrailingseps=0) {
// parse a delimited data string into a multi-dimensional object/array hierarchy, where the values were not necessarily quoted, so JSON functions
// are not an option. This is a recursive parser that handles any sequence of arrays, objects and simple data types
// and returns an appropriate js data object, object or array.
// Input e.g. 'Me:{book:"The \"O\'Grady Tales\"",Phone:'1234567890',Children:{Pierre:["4 Dec 2015","Grade 1","121cm",{id:612,rel:21}],Aisha:["15 Jun 2018","","85cm",{id:684}]}}'
// if  notrailingseps = 1 then a trailing sep means there's a blank value as the last element in the array (4,,34,) => array[3]=""
  var arrayout = [], objectout =  {}, key = '', i = 0, trailingblankstr=0;

    function fw_stripquotes(str) {
      if (typeof str === "string"){
        while ((str.startsWith('"') && str.endsWith('"')) || (str.startsWith("'") && str.endsWith("'"))) {
          str = str.substring(1,str.length-1)
        }
      }
      return str;
    }

    function fw_processval(key,val){
      var testval;
      var testval = val.trim();
      // is this value a {...} or a [...] needing to be parsed by recursive call?
      if (testval.startsWith("{") && testval.endsWith("}")) {
        val = StringParseToObjOrArray(testval.substring(1,testval.length-1),sep,1,notrailingseps);
      } else if (testval.startsWith("[") && testval.endsWith("]"))  {
        val = StringParseToObjOrArray(testval.substring(1,testval.length-1),sep,0,notrailingseps);
      }
      // remove existing wrapping quotes
      val = stripquotes(val);
      if (objectwanted === 1) {
        key = stripquotes(key);
        objectout[key.trim()] = val;
      } else {
        if (key.trim() !== '') {
          // turn an unwrapped key:val pair into a single-property object within the array
          key = stripquotes(key);
          arrayout.push({[key.trim()] : val});
        } else {
          arrayout.push(val);
        }
      }
    }

  if (str !== undefined) {
    while (i <= str.length - 1) {
      if (str.substring(i).startsWith(sep) || i === str.length ) {
        var s1 = str.substring(i);
        var s2 = str.substring(0,i);
        trailingblankstr = (notrailingseps && str.substring(i).endsWith(sep))
        processval(key,str.substring(0,i))
        str = str.substring(i + sep.length);
        i = -1;
        key = '';
      } else if (str[i] === ":" && str[i-1] !== "\\" && str.substring(0,i).trim() !== "") {
        // this un-escaped colon follows a token so take it to be part of a key:val pair
        key = str.substring(0,i);
        str = str.substring(i+1);
        i = -1;
      }
      var target = GetTarget(str, i); // looking for un-escaped < " ' { or ] >
      if (target !== "") {
        i = FindMatch(str,i+1,target);
      }
      i++;
    }
    if (str.trim().length > 0 || trailingblankstr) {
      processval(key,str);
    }
    if (objectwanted === 1) {
      return objectout;
    } else {
      return arrayout;
    }
  } else {
    return "";
  }
}
function fw_nowstring(format=0) {
    const now = new Date();
    let nowstring = "Invalid format supplied";
    let day = now.getDate().toString();
    let month = now.getMonth().toString();
    let year = now.getDate().toString();
    let hour = now.getHours().toString();
    let mins = now.getMinutes().toString();
    let secs = now.getSeconds().toString();
    switch (format) {
      case 0 : nowstring =  day.padStart(2,"0") + "-" + month.padStart(2,"0")  + "-" + now.getFullYear() + "  "+ hour.padStart(2,"0") + ":" + mins.padStart(2,"0");break;
      case 1 : nowstring =  now.getFullYear() +  month.padStart(2,"0")  + day.padStart(2,"0") +  hour.padStart(2,"0") + mins.padStart(2,"0")+ secs.padStart(2,"0");break;
      case 2 : nowstring =  now.getFullYear() +"-"+  month.padStart(2,"0") +"-"+ day.padStart(2,"0") +" "++  hour.padStart(2,"0") +":"++ mins.padStart(2,"0")+":"+ secs.padStart(2,"0");break;
    }
    return nowstring;
}
function fw_NotVisible(container,elem,mustbeallvisible){ // returns: -1 if the element is too high in the container, 
  //          +1 if the element is too low in the container
  //           0 if the element is visible
    let offset = jQuery(container).offset()
    const containerTop = offset.top;
    const containerBottom = containerTop + jQuery(container).height();
    offset = jQuery(elem).offset()
    const elemTop = offset.top;
    const elemBottom = elemTop + jQuery(elem).height();
    const result = mustbeallvisible ? 
                  ((elemTop < containerTop)? -1 : ((elemBottom > containerBottom)? 1 : 0 )) :
                  ((elemBottom <= containerTop)? -1 : ((elemTop >= containerBottom)? 1 : 0 )); 
    return result;
}
function fw_MakeVisible(container,elem,mustbeallvisible,increment=5){
    if (increment === 1) increment = 2;
    if (increment === -1) increment = -2;
    let visibility = NotVisible(container,elem,mustbeallvisible);
    if (visibility === -1) {
        container.scrollTop(container.scrollTop()-1)
        while (NotVisible(container,elem,mustbeallvisible) === -1) {
          let goto  = container.scrollTop() - increment;
          container.scrollTop(goto);
        } 
    } else if (visibility === 1) {
        container.scrollTop(container.scrollTop()+1)
        while (NotVisible(container,elem,mustbeallvisible) === 1) {
          let goto  = container.scrollTop() + increment;
          container.scrollTop(goto);
        } 
    }
}