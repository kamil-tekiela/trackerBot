This is a simple Bot which uses Stack Overflow public API and chat to continuously scan new questions and report in chat the ones which are most likely not written in English. 

To execute create `config.ini` file in main directory containing:

```
chatUserEmail = "chat username"
chatUserPassword = "chat password"
; ID of log chatroom
trackRoomId = 
; Message text for NELQA
NELQA_report_text = "[ [name of the bot](link to StackApps) ] [Link to question](%s) %s" 
; List of chatrooms to report to
[chatrooms]
NELQA[] = 
NELQA[] = 
mysqli[] = 
```

Execute either `public/tracker.php` or execute it asynchronously using `public/startTracker.php`
