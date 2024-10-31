      .tabs-bar {
        overflow: hidden;
        padding-top: 6px;
        margin-top: 10px;
        margin-bottom: 10px;
      }

      .tabs-bar p {
        border-bottom: 1px solid #dfdfdf;
        padding: 2px 10px;
        margin: 0px;
      }

      .tab {
        border: 1px solid #dfdfdf;
        padding: 3px 8px;
        margin-right: 6px;
        background: #f6f6f6;
        text-decoration: none;

        -moz-border-radius-topleft: 2px;
        border-top-left-radius: 2px;
        -moz-border-radius-topright: 2px;
        border-top-right-radius: 2px;

        -moz-box-shadow: 0px 2px 4px #e6e6e6;
        -webkit-box-shadow: 0px 2px 4px #e6e6e6;
        box-shadow: 0px 2px 4px #e6e6e6;
      }

      .tab:hover { text-decoration: underline; }

      .tab.current {
        border-bottom: 1px solid white;
        background: white;
      }

      .pane { display: none; padding: 0px 8px; }
      .pane.current { display: block; }

      .pane .leftcol {
        width: 49%;
        float: left;
      }
      .pane .rightcol {
        width: 49%;
        float: right;
      }
      .faq p {
        padding-left: 10px;
      }

      .pagelink {
        border: 1px solid #ddd;
        -moz-box-shadow: 0px 1px 1px #ddd;
        -webkit-box-shadow: 0px 1px 1px #ddd;
        box-shadow: 0px 1px 1px #ddd;

        -moz-border-radius: 3px;
        border-radius: 3px;
        padding: 2px 5px;
        margin: 2px;
        text-decoration: none;
      }

      .warning { color: darkred; }
      .warning span { font-weight: bold; }

      .button.erase { color: darkred; }
      .button.erase:hover { border-color: darkred; }

      pre {
        background: #f6f6f6;
        border: 1px solid #dfdfdf;
        padding: 3px 6px;
        margin: 6px 10px;
        overflow: hidden;
      }

      pre .preamble { color: green; font-weight: bold; }
      pre .comment { color: darkblue; }
      pre .fn { color: darkred; }
      pre .string { color: olive; font-weight: bold; }
