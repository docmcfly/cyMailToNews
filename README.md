# Typo3 extension cyMailToNews

## Change log

* 0.9.6 FIX : Checkbox in the scheduler configuration works as expected. 
* 0.9.5 CHG : Change the extension icon.
* 0.9.4 FIX : Manipulation rules for the news content rendering.
* 0.9.3 FIX : htmlspecialchars encoding / decoding
* 0.9.2 FIX : Fix the mail part analyse (mail in mail problem)
* 0.9.1 alpha version
* 0.9.0 INIT initial

## Templates

You can descript the form of your news title or news body.

### A very simple example

Title:

```plain
Automatic news
```

=> the title is always "Automatic news"

### A simple example

Title:

```plain
{Subject}
```

=> Your title is replaced with the subject.

### Combination of very simple and simple example

Title:

```plain
Automatic news: {Subject}
```

=> Your title is a combination of the static text: "Automatic news: " and the dynamic subject part. 

### A simple example with a special tag

Body:

```plain
{body}
```

=> Your body is replaced with the mail text body. You have three body options:

* ```"bodyHtml"``` returns the HTML text of the email (if available)
* ```"bodyPlain"``` returns the plain text of the email (if available)
* ```"body"``` returns the HTML text of the email, if available; otherwise, the plain text.

### A stronger example with replacement

You can manipulate the content of the replacement.

Title:

```plain
{Subject pattern="^\[spam] (.*)$" replacement="$1"}
```

=> Example set the title with the mail subject without the start word "[spam]".

**HINT:** The replacement rule will ignore if the pattern does not matched. In this example: When
your mail subject has not a spam marker, the news title has the original subject.   

## Filter rules

The filter rules are defined in a JSON format. When the regex rule (set) matches, the task creates a news from the mail and mark this mails as "readed".

```json
{
    "Subject": "[regex]",
    "body": "[regex]"
}
```

```[regex]``` is a placeholder for a regular expression.

The keys are mail header keys or one of these special keys:

* ```"bodyHtml"``` returns the HTML text of the email (if available)
* ```"bodyPlain"``` returns the plain text of the email (if available)
* ```"body"``` returns the HTML text of the email, if available; otherwise, the plain text.

## Category rules

The category rules are defined in a JSON format. When a regex rule (set) matches, the news is added with the specified category.

```json
{
   "[categoryUid]": {
       "Subject": "[regex]",
       "body": "[regex]"
   }
}
```

```[categoryUid]``` is a placeholder for an integer and the category identifier.
```[regex]``` is a placeholder for a regular expression.

The keys are mail header keys or one of these special keys:

* ```"bodyHtml"``` returns the HTML text of the email (if available)
* ```"bodyPlain"``` returns the plain text of the email (if available)
* ```"body"``` returns the HTML text of the email, if available; otherwise, the plain text.

