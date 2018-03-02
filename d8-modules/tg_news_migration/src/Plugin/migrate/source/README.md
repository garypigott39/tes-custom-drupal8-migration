# News Migration Source Plugins

The approach has been to create a source plugin for each migration type. 

The rationale is that (a) the source plugins are easy to write and (b) the source data may need massaging or *filtering which is kind of a @todo.*

The exception is the file NewsFileCSV.php (migrate source "news_file_csv") which is used for all entity files, which includes author images and any assets associated with news articles.

## Extended base class

If you have a look at the plugins they are all extending the abstract class "TesNewsCSV" which is itself extending the "BaseCSV" class (see **migrate_source_csv** contrib module).

The only reason for the TesNewsCSV class is the file error-handling which results in a more *graceful way of dealing with the source file not existing.*


 