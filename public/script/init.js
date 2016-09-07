var com = {
    date: {
        oneDayInMillis: 864E5,
        ArriveDefault: new Date((new Date).getTime() + 120 * 864E5),
        DepartDefault: new Date((new Date).getTime() + 122 * 864E5),
        ArriveAmendDefault: new Date((new Date).getTime() + 65 * 864E5),
        DepartAmendDefault: new Date((new Date).getTime() + 70 * 864E5),
        timestamp: new Date().getTime()
    },
    elm: {
        hashtag: ''
    },
    agent: {
        id: 'Agoda',
        login: '1656328',
        password: '950F0CF6-F6E6-4646-B050-A21414D44ACC',
        InternalCode: 'CL958'
    },
    bookinfo:
            {
                OsRef: new Date().getFullYear() + ("0" + (new Date().getMonth() + 1)).slice(-2) + new Date().getDate() + Math.floor((Math.random() * 1000) + 1) + "_test"

            }
};



//Set dynamic arrive and departure date.
com.date.ArriveDate = com.date.ArriveDefault.getFullYear() + "-" + ("0" + (com.date.ArriveDefault.getMonth())).slice(-2) + "-" + ("0" + (com.date.ArriveDefault.getDate())).slice(-2);
com.date.DepartDate = com.date.DepartDefault.getFullYear() + "-" + ("0" + (com.date.DepartDefault.getMonth())).slice(-2) + "-" + ("0" + (com.date.DepartDefault.getDate())).slice(-2);

com.date.ArriveDateAmend = com.date.ArriveAmendDefault.getFullYear() + "-" + ("0" + (com.date.ArriveAmendDefault.getMonth())).slice(-2) + "-" + ("0" + (com.date.ArriveAmendDefault.getDate())).slice(-2);
com.date.DepartDateAmend = com.date.DepartAmendDefault.getFullYear() + "-" + ("0" + (com.date.DepartAmendDefault.getMonth())).slice(-2) + "-" + ("0" + (com.date.DepartAmendDefault.getDate())).slice(-2);

//get hash tag
com.elm.hashtag = window.location.hash.replace(/^#/, '');
