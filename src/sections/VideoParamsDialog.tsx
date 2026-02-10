import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { AspectRatio } from '@/types';

interface VideoParamsDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  aspectRatio: AspectRatio;
  onAspectRatioChange: (ratio: AspectRatio) => void;
  duration: 5 | 10;
  onDurationChange: (duration: 5 | 10) => void;
  quality: 'standard' | 'high';
  onQualityChange: (quality: 'standard' | 'high') => void;
}

const videoAspectRatios: { value: AspectRatio; label: string }[] = [
  { value: '16:9', label: '16:9' },
  { value: '1:1', label: '1:1' },
  { value: '9:16', label: '9:16' },
];

export default function VideoParamsDialog({
  open,
  onOpenChange,
  aspectRatio,
  onAspectRatioChange,
  duration,
  onDurationChange,
  quality,
  onQualityChange,
}: VideoParamsDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-[400px] p-0 gap-0">
        <DialogHeader className="px-5 py-4 border-b border-[#E5E5E5]">
          <DialogTitle className="text-base font-medium text-[#1A1A1A]">视频设置</DialogTitle>
        </DialogHeader>
        
        <div className="p-5 space-y-6">
          {/* Quality Selection */}
          <div>
            <label className="text-sm text-[#666666] mb-2 block">生成品质</label>
            <div className="flex p-1 bg-[#F5F5F5] rounded-lg">
              <button
                onClick={() => onQualityChange('standard')}
                className={cn(
                  "flex-1 py-2 text-sm rounded-md transition-all duration-200",
                  quality === 'standard' 
                    ? "bg-white text-[#1A1A1A] shadow-sm" 
                    : "text-[#666666] hover:text-[#1A1A1A]"
                )}
              >
                标准模式
              </button>
              <button
                onClick={() => onQualityChange('high')}
                className={cn(
                  "flex-1 py-2 text-sm rounded-md transition-all duration-200",
                  quality === 'high' 
                    ? "bg-white text-[#1A1A1A] shadow-sm" 
                    : "text-[#666666] hover:text-[#1A1A1A]"
                )}
              >
                高品质模式
              </button>
            </div>
          </div>

          {/* Aspect Ratio */}
          <div>
            <label className="text-sm text-[#666666] mb-3 block">视频比例</label>
            <div className="grid grid-cols-3 gap-2">
              {videoAspectRatios.map((ratio) => (
                <button
                  key={ratio.value}
                  onClick={() => onAspectRatioChange(ratio.value)}
                  className={cn(
                    "flex flex-col items-center gap-1.5 p-2 rounded-lg border transition-all duration-200",
                    aspectRatio === ratio.value
                      ? "border-[#3B82F6] bg-[#F0F7FF]"
                      : "border-[#E5E5E5] hover:border-[#3B82F6]"
                  )}
                >
                  <div 
                    className={cn(
                      "border-2 rounded-sm",
                      aspectRatio === ratio.value ? "border-[#3B82F6]" : "border-[#999999]"
                    )}
                    style={{
                      width: ratio.value === '1:1' ? 16 : ratio.value === '16:9' ? 18 : 12,
                      height: ratio.value === '1:1' ? 16 : ratio.value === '16:9' ? 12 : 18,
                    }}
                  />
                  <span className={cn(
                    "text-[10px]",
                    aspectRatio === ratio.value ? "text-[#3B82F6]" : "text-[#666666]"
                  )}>{ratio.label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Duration */}
          <div>
            <label className="text-sm text-[#666666] mb-2 block">视频时长</label>
            <div className="flex p-1 bg-[#F5F5F5] rounded-lg">
              <button
                onClick={() => onDurationChange(5)}
                className={cn(
                  "flex-1 py-2 text-sm rounded-md transition-all duration-200",
                  duration === 5 
                    ? "bg-white text-[#1A1A1A] shadow-sm" 
                    : "text-[#666666] hover:text-[#1A1A1A]"
                )}
              >
                5s
              </button>
              <button
                onClick={() => onDurationChange(10)}
                className={cn(
                  "flex-1 py-2 text-sm rounded-md transition-all duration-200",
                  duration === 10 
                    ? "bg-white text-[#1A1A1A] shadow-sm" 
                    : "text-[#666666] hover:text-[#1A1A1A]"
                )}
              >
                10s
              </button>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
