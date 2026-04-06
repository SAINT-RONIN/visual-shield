# AI Usage in Visual Shield

I used AI mainly as a learning and support tool while building this project.

The biggest area where AI helped me was understanding how FFmpeg works and how I could connect it to my project and API. This was something I had never done before. At first I thought it would be simple, but it turned out to be much more difficult than I expected, so AI helped me understand the general workflow, the purpose of FFmpeg and FFprobe, and how those tools could fit into my backend. I also did not know how to properly use shell commands for this kind of feature, so I used AI to help me generate the shell commands for the things I wanted to do, such as reading video metadata and extracting frames from uploaded videos. AI also helped me understand what extracting frames was actually doing, why that step was important, and how it connected to the rest of my analysis. More specifically, it helped me understand that extracting frames turns a video into individual images that my backend can inspect one by one, which is important because things like luminance and frame difference need to be calculated from image pixels rather than from the raw video file itself. I also used AI to better understand and create the image analysis logic that calculates luminance, compares frames, and supports the other visual checks used in the project.

I also used AI to help me understand Atomic Design on the frontend. The way I used it was by copying and pasting entire HTML snippets from my pages and asking AI to explain where the boundaries between atoms, molecules, and organisms were. Finding atoms was straightforward because they are small isolated elements like buttons, labels, and inputs. The harder part was understanding why a certain group of elements should be a molecule and not an organism or the other way around, and AI helped me reason through those decisions by looking at the full HTML context I provided.

## Examples of How I Used AI

Here are some concrete examples of where AI helped me during development:

- Understanding the difference between FFmpeg and FFprobe, and when I should use one to extract frames and the other to read metadata such as video duration and resolution.
- Understanding how a video upload could move through my backend, for example: upload the file, store it safely, queue it for processing, extract frames, run analysis, and save the results to the database.
- Learning why shell commands needed to be handled carefully when working with file paths and user uploads, especially when connecting FFmpeg commands to a PHP backend.
- Understanding what frame extraction actually means, which is converting the video into a sequence of image files so the backend can inspect visual data frame by frame.
- Understanding why extracted frames are important for calculations such as luminance, because the brightness values need to be calculated from the pixels inside each frame image.
- Getting help understanding and creating the image-analysis part that samples pixels, converts RGB values into luminance, compares frames, and produces values that support flash and motion detection.
- Using AI to help me better organise the Atomic Design structure of my frontend by pasting full HTML snippets and getting explanations on where each level started and ended.






-------------------------------------------------------------------------------------






## Code Examples

These are simple examples of the kind of things AI helped me understand while building the project.

### 1. Shell command example for FFprobe

AI helped me understand how to generate a shell command to read video metadata, for example the duration of a video:

```bash
ffprobe -v error -show_entries format=duration -of csv=p=0 "video.mp4"
```

This was useful because I needed to understand how to get information from a video before saving or analyzing it.

### 2. Shell command example for FFmpeg

AI also helped me understand how to generate a shell command to extract frames from a video:

```bash
ffmpeg -i "video.mp4" -vf fps=10 "frames/frame_%05d.jpg"
```

This was important because my analysis logic depends on comparing image frames instead of reading the full video directly.

### 3. PHP example of running shell commands safely

Another thing AI helped me with was understanding how to connect shell commands to PHP safely:

```php
$safeFilePath = escapeshellarg($filePath);
$command = "ffprobe -v error -show_entries format=duration -of csv=p=0 {$safeFilePath}";
$output = shell_exec($command);
```

and:

```php
$safeInputPath = escapeshellarg($videoPath);
$safeOutputPattern = escapeshellarg($outputDirectory . '/frame_%05d.jpg');
$command = "ffmpeg -i {$safeInputPath} -vf fps=10 {$safeOutputPattern} 2>&1";
exec($command, $commandOutput, $exitCode);
```

This helped me understand how PHP can work together with FFmpeg and FFprobe inside the backend.

### 4. PHP example of calculating luminance from image pixels

AI also helped me understand the kind of logic needed after frame extraction, for example calculating luminance from the RGB values inside an extracted image:

```php
$r = ($rgb >> 16) & 0xFF;
$g = ($rgb >> 8) & 0xFF;
$b = $rgb & 0xFF;

$luminance = 0.299 * $r + 0.587 * $g + 0.114 * $b;
```

This mattered because once the video was turned into frames, I needed a way to measure brightness and compare visual changes between frames as part of the accessibility analysis.

### 5. Atomic Design, how I used AI with HTML snippets

I did not ask AI abstract questions about Atomic Design. Instead I copied and pasted entire HTML sections from my pages and asked AI to explain where atoms, molecules, and organisms were within that specific code.

For example I pasted a full login page section that looked roughly like this:

```html
<div class="flex flex-col gap-6">
  <div class="space-y-2">
    <label class="text-sm font-medium">Username</label>
    <input class="w-full rounded-lg border px-4 py-2" />
  </div>
  <div class="space-y-2">
    <label class="text-sm font-medium">Password</label>
    <input class="w-full rounded-lg border px-4 py-2" type="password" />
  </div>
  <button class="rounded-lg bg-indigo-600 px-4 py-2 text-white">Log in</button>
</div>
```

From that AI explained:

- The `<button>` and each `<input>` were atoms because they were single UI elements with no internal structure worth splitting further.
- Each `<label>` + `<input>` pair wrapped in a `div` was a molecule because two atoms were grouped together into a small self-contained unit with a clear purpose.
- The outer `<div>` combining multiple field molecules and the submit button was an organism because it assembled several smaller parts into one complete functional section of the page.

This approach helped me because I could see exactly which lines of my own HTML belonged to each level, instead of trying to understand the pattern from a theoretical description. Atoms were always easy to spot once I had the full HTML in front of me. The hard decisions was always whether something was a molecule or an organism, and AI helped me reason through that by looking at how many grouped responsibilities each block had.

AI was not used as a replacement for my own work. I used it to explain concepts, help me understand unfamiliar tools, and support my thinking when structuring parts of the project. I still had to review the results, decide what made sense for my project, and adapt the code and structure to fit Visual Shield.
