# Typo3 extension cyMailToNews

## Change log

* 0.9.1 alpha version
* 0.9.0 INIT initial


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

